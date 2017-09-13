<?php

namespace Swoole\Core\Lib\Cache\Drivers;

use Swoole\Core\Config;
use Swoole\Core\Log;
use \swoole_table;
use \swoole_lock;

class Cache_table {

    protected $memory;
    private $limit_table_value;
    private $limit_table_count;

    function __construct() {
        //条件组合内存管理表
        $limit_table_count = Config::instance()->get('cache[tablelimit]');
        $limit_table_count = $limit_table_count ? $limit_table_count : 1000000;
        $limit_table_value = Config::instance()->get('cache[table_valuelimit]');
        $limit_table_value = $limit_table_value ? $limit_table_value : 64;
        $this->limit_table_count = $limit_table_count;
        $this->limit_table_value = $limit_table_value;

        $this->memory = new swoole_table($limit_table_count);
        $this->memory->column('value', swoole_table::TYPE_STRING, $limit_table_value);
        $this->memory->column('key', swoole_table::TYPE_STRING, 64);
        $this->memory->column('ttl', swoole_table::TYPE_INT, 4); //过期时间，单位秒。不填或者0为永不过期。
        $this->memory->column('time', swoole_table::TYPE_INT, 8); //保存时间
        $this->memory->create();
    }

    function get_cache($key) {
        $memory = $this->memory->get($key);
//        Log::writelog("本机内存中获取key:" . $key . " value:" . $memory['value']);
        if (!empty($memory)) {
            $ttl = intval($memory['ttl']);
            $settime = intval($memory['time']);
            if (!$ttl || ($ttl && ($ttl + $settime > time()))) {
                return $memory['value'];
            } else {
                $this->del_cache($key);
                return false;
            }
        } else {
            return FALSE;
        }
    }

    function set_cache($key, $value, $ttl = 0) {
        if (is_array($value) || is_object($value) || is_resource($value)) {
            $data = serialize($value);
        }
        if (strlen($value) <= $this->limit_table_value) {
            $this->memory->set($key, array("value" => $value, "key" => $key, 'ttl' => $ttl, 'time' => time()));
        } else {
            Log::writelog("缓存保存出错：'{$key}' 的值的长度超过了设置的允许的最长长度！");
        }
    }

    function del_cache($key) {
        //Log::writelog("......删除缓存(key:$key)开始......");
        @$retrun = $this->memory->del($key);
        //Log::writelog("......删除缓存(key:$key)" . ($retrun ? "成功" : "失败") . "......");
        return $retrun;
    }

    function count() {
        return $this->memory->count();
    }

    function get_memory() {
        return $this->memory;
    }

    function lock() {
        $this->memory->lock();
    }

    function unlock() {
        $this->memory->unlock();
    }

    function __get($name) {
        if ($name == 'memory') {
            return $this->memory;
        } else {
            return false;
        }
    }

    function clean() {
        foreach ($this->memory as $key => $mtable) {
            $this->memory->del($mtable['key']);
        }
    }

    //清理过期缓存
    function clean_expired_cache() {
        foreach ($this->memory as $key => $memory) {
            if (!empty($memory)) {
                $ttl = intval($memory['ttl']);
                $settime = intval($memory['time']);
                if ($ttl && ($ttl + $settime <= time()) && $memory['key']) {
                    $this->del_cache($memory['key']);
                }
            }
        }
    }

}
