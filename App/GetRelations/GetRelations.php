<?php

namespace Swoole\App\GetRelations;

use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\SwooleRedis;
use Swoole\Core\Lib\RedisClus;
use Swoole\App\GetRelations\Logic\DumpData;
use Swoole\App\GetRelations\Logic\Relations;
use Swoole\App\GetRelations\Logic\IncRelations;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DataEtl
 *
 * @author root
 */
class GetRelations extends \Swoole\Core\App\Controller {

    private $dump_data;
    private $get_relations;

    function __construct() {
        parent::__construct();
    }

    public function init() {
        $this->get_relations = new Relations($this);
        Log::writelog("worker[{$this->swoole->worker_id}] object init ......");
    }

    public function get_relations($params) {
        $this->get_relations->index($params);
    }

    /*
     * 导出全量数据
     */

    public function dump_main($params) {
        //获取当前mysql 库下面所有的表名
        $this->dump_data = new DumpData($this);
        $this->dump_data->dump_main($params);
    }

    /*
     * 异步回调
     */

    function main_back_call($data) {
        $this->dump_data->main_back_call($data);
    }

    /*
     * 写入数据分发
     */

    function save_send($params, $call_back = false) {
        if (!$this->dump_data) {
            $this->dump_data = new DumpData($this);
            $this->dump_data->init_config($params);
        }
        return $this->dump_data->save_send($params);
    }

    public function client_close($data) {
        $fd = $data["fd"];
        $this->get_relations->client_close($fd);
        Log::write_log("client[{$fd}] closed ......");
    }

    public function __destruct() {
        
    }

    public function __wakeup() {
        
    }

}
