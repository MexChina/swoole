<?php

namespace Swoole\Core\Lib\Memory\Drivers;

use \Memcached;
use Swoole\Core\Log;

class Memory_memcached {

    public $enable;
    public $obj;

    public function init($config) {
        if ($config['pconnect']) {
            $this->obj = new Memcached(md5($config['server'] . $config['port']));
        } else {
            $this->obj = new Memcached;
        }
        $this->obj->setOption(Memcached::OPT_TCP_NODELAY, true); //启用tcp_nodelay
        $this->obj->setOption(Memcached::OPT_NO_BLOCK, true); //启用异步IO
        $this->obj->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT); //分布式策略
        $this->obj->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true); //分布式服务组分散.推荐开启 
        $this->obj->setOption(Memcached::OPT_HASH, Memcached::HASH_CRC);  //Key分布
        $connect = $this->obj->addServer($config['server'], $config['port'], 100);
        $this->enable = $connect ? true : false;
        Log::writelog("memcached connect " . ($connect ? "succes" : "failed") . "......");
    }

    public function get($key) {
        return $this->obj->get($key);
    }

    public function getMulti($keys) {
        return $this->obj->get($keys);
    }

    public function set($key, $value, $ttl = 0) {
        return $this->obj->set($key, $value, $ttl);
    }

    public function rm($key) {
        return $this->obj->delete($key);
    }

    public function delete($key) {
        return $this->obj->delete($key);
    }

    public function clear() {
        return $this->obj->flush(0);
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