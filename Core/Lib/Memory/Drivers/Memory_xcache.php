<?php

namespace Swoole\Core\Lib\Memory\Drivers;

class Memory_xcache {

    public function init($config) {
        
    }

    public function get($key) {
        return xcache_get($key);
    }

    public function set($key, $value, $ttl = 0) {
        return xcache_set($key, $value, $ttl);
    }

    public function rm($key) {
        return xcache_unset($key);
    }

    public function clear() {
        if (extension_loaded('XCache') && function_exists("xcache_clear_cache") && defined("XC_TYPE_VAR")) {
            return xcache_clear_cache(XC_TYPE_VAR, -1);
        }
    }

    public function inc($key, $step = 1) {
        return xcache_inc($key, $step);
    }

    public function dec($key, $step = 1) {
        return xcache_dec($key, $step);
    }

}
