<?php

namespace Swoole\Core\Lib;

use \swoole_atomic;
use Swoole\Core\Helper\File;
use Swoole\Core\Log;
use Swoole\Core\AppServer;

/**
 * 程序运行锁，多进程处理的时候，加锁的程序片段将会是线程安全的
 * 
 */
class Lock {

    private $atomic;
    private $lock_value = 0;

    function __construct() {
        $this->atomic = new swoole_atomic(0);
    }

    function lock() {
        while (TRUE) {
            $get_lock = $this->atomic->cmpset(0, 1);
            if ($get_lock) {
                $this->lock_value = 1;
                //Log::writelog("[" . microtime(TRUE) . " workerid:" . AppServer::instance()->swoole->worker_id . "] lock success ......");
                return TRUE;
            }
        }
    }

    public function unlock() {
        if ($this->lock_value) {
            $get_lock = $this->atomic->cmpset(1, 0);
            if ($get_lock) {
                $this->lock_value = 0;
                //Log::writelog("[" . microtime(TRUE) . " workerid:" . AppServer::instance()->swoole->worker_id . "] unlock success ......");
                return true;
            }
        } else {
            return false;
        }
    }

}
