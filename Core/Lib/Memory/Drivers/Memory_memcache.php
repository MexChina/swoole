<?php

namespace Swoole\Core\Lib\Memory\Drivers;

use \Memcache;
use Swoole\Core\Log;

class Memory_memcache {

    public $enable;
    public $obj;

    public function init($config) {
        if (!empty($config['server'])) {
            $this->obj = new Memcache;
            if ($config['pconnect']) {
                $connect = $this->obj->pconnect($config['server'], $config['port']);
            } else {
                $connect = $this->obj->connect($config['server'], $config['port']);
            }
            $this->enable = $connect ? true : false;
            Log::writelog("memcache connect " . ($connect ? "succes" : "failed") . "......");
        }
    }

    public function get($key) {
        $value = $this->obj->get($key);
        return $value;
    }

    public function getMulti($keys) {
        return $this->obj->get($keys);
    }

    public function set($key, $value, $ttl = 0) {
        return $this->obj->set($key, $value, MEMCACHE_COMPRESSED, $ttl);
    }

    public function rm($key) {
        return $this->obj->delete($key);
    }

    public function clear() {
        return $this->obj->flush();
    }

    public function inc($key, $step = 1) {
        return $this->obj->increment($key, $step);
    }

    public function dec($key, $step = 1) {
        return $this->obj->decrement($key, $step);
    }

    public function __call($name, $arguments) {
        if (!method_exists($this, $name)) {
            return call_user_func_array(array($this->obj, $name), $arguments);
        }
    }

}

?>