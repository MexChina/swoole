<?php

namespace Swoole\Core\Lib\Memory;

use Swoole\Core\Core;
use Swoole\Core\Config;
use Swoole\Core\Log;
use Swoole\Core\Lib\Memory\Drivers\Memory_memcache;
use Swoole\Core\Lib\Memory\Drivers\Memory_memcached;
use Swoole\Core\Lib\Memory\Drivers\Memory_apc;
use Swoole\Core\Lib\Memory\Drivers\Memory_wincache;
use Swoole\Core\Lib\Memory\Drivers\Memory_eaccelerator;
use Swoole\Core\Lib\Memory\Drivers\Memory_redis;

class Memory {

    protected $valid_drivers = array(
        'memory_apc', 'memory_redis', 'memory_memcached', 'memory_memcache', 'memory_xcache', 'memory_eaccelerator', 'memory_wincache'
    );
    private $config;
    private $extension = array();
    private $memory;
    private $prefix;
    private $userprefix;
    public $type;
    public $enable = false;
    public $debug = array();
    public static $mob = null;

    public function __construct() {
        if (is_object(self::$mob)) {
            return self::$mob;
        } else {
            //加载内存配置文件
            $this->config = Config::instance()->get("memory");
            $this->extension['redis'] = extension_loaded('redis');
            $this->extension['memcache'] = extension_loaded('memcache');
            $this->extension['memcached'] = extension_loaded('memcached');
            $this->extension['apc'] = function_exists('apc_cache_info') && @apc_cache_info();
            $this->extension['xcache'] = function_exists('xcache_get');
            $this->extension['eaccelerator'] = function_exists('eaccelerator_get');
            $this->extension['wincache'] = function_exists('wincache_ucache_meminfo') && wincache_ucache_meminfo();
            $this->init();
//            echo "内存是否加载完毕：\n";
//            var_dump($this->extension);
        }
    }

    public static function getMemory() {
        if (is_object(self::$memory)) {
            return self::$mob;
        } else {
            new Memory();
            return Memory::$mob;
        }
    }

    public function init() {
        if (empty($this->config)) {
            return;  //没有内存缓存配置文件
        }
        $config = $this->config;
        $this->prefix = empty($config['prefix']) ? substr(md5($_SERVER['HTTP_HOST']), 0, 6) . '_' : $config['prefix'];
        if ($this->extension['redis'] && !empty($config['redis']['server'])) {
            include_once SWOOLE_ROOT_DIR . "/Core/Lib/Memory/Drivers/Memory_redis.php";
            $this->memory = new Memory_redis;
            $this->memory->init($config['redis']);
            if (!$this->memory->enable) {
                $this->memory = null;
            }
        }

        if ($this->extension['memcached'] && !empty($config['memcached']['server'])) {
            include_once SWOOLE_ROOT_DIR . "/Core/Lib/Memory/Drivers/Memory_memcached.php";
            $this->memory = new Memory_memcached;
            $this->memory->init($this->config['memcached']);
            if (!$this->memory->enable) {
                $this->memory = null;
            }
        }

        if ($this->extension['memcache'] && !empty($config['memcache']['server'])) {
            include_once SWOOLE_ROOT_DIR . "/Core/Lib/Memory/Drivers/Memory_memcache.php";
            $this->memory = new Memory_memcache;
            $this->memory->init($this->config['memcache']);
            if (!$this->memory->enable) {
                $this->memory = null;
            }
        }

        foreach (array('apc', 'eaccelerator', 'xcache', 'wincache') as $cache) {
            if (!is_object($this->memory) && $this->extension[$cache] && $this->config[$cache]) {
                include_once SWOOLE_ROOT_DIR . "/Core/Lib/Memory/Drivers/Memory_{$cache}.php";
                $memoryClass = "Memory_{$cache}";
                $this->memory = new $memoryClass;
                $this->memory->init(null);
            }
        }

        if (is_object($this->memory)) {
            $this->enable = true;
            $this->type = str_replace('Memory_', '', get_class($this->memory));
        }
    }

    public function get($key, $prefix = '') {
        $ret = false;
        if ($this->enable) {
            $this->userprefix = $prefix;
            if (is_array($key)) {
                $getmulti = false;
                $getmulti = method_exists($this->memory, 'getMulti');
                if ($getmulti) {
                    $ret = $this->memory->getMulti($this->_key($key));
                    if ($ret !== false && !empty($ret)) {
                        $_ret = array();
                        foreach ((array) $ret as $_key => $value) {
                            $_ret[$this->_trim_key($_key)] = $value;
                        }
                        $ret = $_ret;
                    }
                } else {
                    $ret = array();
                    $_ret = false;
                    foreach ($key as $id) {
                        if (($_ret = $this->memory->get($this->_key($id))) !== false && isset($_ret)) {
                            $ret[$id] = $_ret;
                        }
                    }
                }
                if (empty($ret))
                    $ret = false;
            } else {
                $ret = $this->memory->get($this->_key($key));
                if (!isset($ret))
                    $ret = false;
            }
        }
        return $ret;
    }

    public function set($key, $value, $ttl = 0, $prefix = '') {

        $ret = false;
        if ($value === false)
            $value = '';
        if ($this->enable) {
            $this->userprefix = $prefix;
            $ret = $this->memory->set($this->_key($key), $value, $ttl);
        }
        return $ret;
    }

    public function rm($key, $prefix = '') {
        $ret = false;
        if ($this->enable) {
            $this->userprefix = $prefix;
            $key = $this->_key($key);
            foreach ((array) $key as $id) {
                $ret = $this->memory->rm($id);
            }
        }
        return $ret;
    }

    public function clear() {
        $ret = false;
        if ($this->enable && method_exists($this->memory, 'clear')) {
            $ret = $this->memory->clear();
        }
        return $ret;
    }

    public function inc($key, $step = 1) {
        static $hasinc = null;
        $ret = false;
        if ($this->enable) {
            if (!isset($hasinc))
                $hasinc = method_exists($this->memory, 'inc');
            if ($hasinc) {
                $ret = $this->memory->inc($this->_key($key), $step);
            } else {
                if (($data = $this->memory->get($key)) !== false) {
                    $ret = ($this->memory->set($key, $data + ($step)) !== false ? $this->memory->get($key) : false);
                }
            }
        }
        return $ret;
    }

    public function dec($key, $step = 1) {
        static $hasdec = null;
        $ret = false;
        if ($this->enable) {
            if (!isset($hasdec))
                $hasdec = method_exists($this->memory, 'dec');
            if ($hasdec) {
                $ret = $this->memory->dec($this->_key($key), $step);
            } else {
                if (($data = $this->memory->get($key)) !== false) {
                    $ret = ($this->memory->set($key, $data - ($step)) !== false ? $this->memory->get($key) : false);
                }
            }
        }
        return $ret;
    }

    private function _key($str) {
        $perfix = $this->prefix . $this->userprefix;
        if (is_array($str)) {
            foreach ($str as &$val) {
                $val = $perfix . $val;
            }
        } else {
            $str = $perfix . $str;
        }
        return $str;
    }

    private function _trim_key($str) {
        return substr($str, strlen($this->prefix . $this->userprefix));
    }

    public function getextension() {
        return $this->extension;
    }

    public function getconfig() {
        return $this->config;
    }

    //内存缓存统计cas

    /**
     * __get()
     *
     * @param 	child
     * @return 	object
     */
    public function __get($child) {
        $obj = parent::__get($child);
        return $obj;
    }

    public function __call($name, $arguments) {
        return call_user_func_array(array($this->memory, $name), $arguments);
    }

    //产生内存标识cas  作为内存同意唯一标识
    //共4部分  第一部分cas 16位毫秒时间戳+8位随机数字, 第二部分命中次数，第三部分平均命中次数
    private function createCas() {
        $timestr = str_pad(round(microtime(true) * 1000), 16, "0", STR_PAD_LEFT);
        $randstr = mt_rand(10000000, 99999999);
        return $timestr . $randstr;
    }

}
