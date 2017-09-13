<?php

namespace Swoole\Core\Lib\Cache\Drivers;

use Swoole\Core\Helper\File;
use Swoole\Core\Config;
use Swoole\Core\Log;

class Cache_files {

    protected $_cache_path;

    function __construct() {
        $path = Config::instance()->get('cache[cache_path]');
        $this->_cache_path = $path == '' ? (SWOOLE_ROOT_DIR . 'cache/') : $path;
    }

    function get_cache($key) {
        if ($this->cache_exists($key)) {
            $data = $this->_get_cache($key);
            return $data['data'];
        }
        return false;
    }

    function set_cache($key, $value, $life) {
        global $_G;
        $data = array($key => array('data' => $value, 'life' => $life));
        $cache_file = $this->get_cache_file_path($key);
        File::creat_dir_with_filepath($cache_file);
        $cachedata = "\$data = " . \Swoole\Core\Helper\MyArray::arrayeval($data) . ";\n";
        if ($fp = @fopen($cache_file, 'wb')) {
            fwrite($fp, "<?php\n//NCSM! cache file, DO NOT modify me!" .
                    "\n//Created: " . date("M j, Y, G:i") .
                    "\n//Identify: " . md5($cache_file . $cachedata . $_G['config']['security']['authkey']) . "\n\n\n$cachedata?>");
            fclose($fp);
        } else {
            Log::write_error('Can not write to cache files, please check path:' . $cache_file . ", $key:$value");
        }
        return true;
    }

    function del_cache($key) {
        if (!trim($key)) {
            return false;
        }
        $cache_file = $this->get_cache_file_path($key);
        if (file_exists($cache_file)) {
            return @unlink($cache_file);
        }
        return true;
    }

    function _get_cache($key) {
        static $data = null;
        if (!isset($data[$key])) {
            include $this->get_cache_file_path($key);
        }
        return $data[$key];
    }

    function cache_exists($key) {
        $cache_file = $this->get_cache_file_path($key);
        if (!file_exists($cache_file)) {
            return false;
        }
        $data = $this->_get_cache($key);
        if ($data['life'] && (filemtime($cache_file) < time() - $data['life'])) {
            $this->del_cache($key);
            return false;
        }
        return true;
    }

    public function clean() {
        return File::clear_dir($this->_cache_path);
    }

    function get_cache_file_path($key) {
        static $cache_path = null;
        if (!isset($cache_path[$key])) {
            $dir = hexdec($key{0} . $key{1} . $key{2}) % 1000;
            $cache_path[$key] = $this->_cache_path . $dir . '/' . $key . '.php';
        }
        return $cache_path[$key];
    }

}
