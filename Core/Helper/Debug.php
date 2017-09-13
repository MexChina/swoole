<?php

namespace Swoole\Core\Helper;

class Debug {

    private static $exec_time = 0;

//计算代码执行时间
    public static function exec_time() {
        $start_time = 0;
        if (self::$exec_time) {
            $start_time = self::$exec_time;
        }
        self::$exec_time = microtime(true);
        return $start_time ? intval((self::$exec_time - $start_time) * 1000) : 0;
    }

}
