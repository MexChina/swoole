<?php

namespace Swoole\Core\Lib;

use Swoole\Core\Lib\Memory\Memory;
use Swoole\Core\Config;
use Swoole\Core\Log;
use Swoole\Core\Lib\Cache\Drivers\Cache_table;
use Swoole\Core\Lib\Cache\Drivers\Cache_files;
use Swoole\Core\Lib\Cache\Drivers\Cache_sql;

/**
 * 持久化缓存类
 *
 * @package share
 * @subpackage	Lib
 * @category	Core
 * @author  Xuelin
 * @link
 */
class Cache {

    protected $valid_drivers = array(
        'cache_files', 'cache_sql', 'cache_table'
    );
    protected $_cache_path = NULL;  // Path of cache files (if file-based cache)
    protected $_adapter = 'table';
    protected $_backup_driver;
    public $memory = NULL;
    public static $instances;
    public $cache;
    public $table;
    private $lock_key = "swoole_share_memory_lockkey";
    private $lock = "";

    // ------------------------------------------------------------------------

    /**
     * Constructor
     *
     * @param array
     */
    public function __construct() {
        $this->_initialize();
    }

    // ------------------------------------------------------------------------

    /**
     * Get
     *
     * Look for a value in the cache.  If it exists, return the data
     * if not, return FALSE
     *
     * @param 	string
     * @return 	mixed		value that is stored/FALSE on failure
     */
    public function get($id) {
        if (!$this->cache || !$return = $this->cache->get_cache($id)) {
            if ($this->memory->enable) {
                $return = $this->memory->get($id);
                if ($return !== FALSE && $this->cache)
                    $this->cache->set_cache($id, $return, 0);
            } else {
                return false;
            }
        }

        if ($aretrun = @unserialize($return)) {
            return $aretrun;
        } else {
            return $return;
        }
    }

    //批量获取缓存值
    public function gets(array $ids) {
        $return = array();
        foreach ($ids as $id) {
            $return[$id] = $this->get($id);
        }
        return $return;
    }

    // ------------------------------------------------------------------------

    /**
     * Cache Save
     *
     * @param 	string		Unique Key
     * @param 	mixed		Data to store
     * @param 	int			Length of time (in seconds) to cache the data
     *
     * @return 	boolean		true on success/false on failure
     */
    public function set($id, $data, $ttl = 0) {
        if (is_array($data) || is_object($data) || is_resource($data)) {
            $data = serialize($data);
        }
        if ($this->memory->enable) {
            $this->memory->set($id, $data, $ttl);
        }
        if ($this->cache)
            $this->cache->set_cache($id, $data, $ttl);
        return TRUE;
    }

    //设置本机内存缓存
    public function setcache($id, $data, $ttl = 0) {
        if (is_array($data)) {
            $data = serialize($data);
        }
        $this->cache->set_cache($id, $data, $ttl);
    }

    //获取本机内存缓存
    public function getcache($id) {
        if (!$return = $this->cache->get_cache($id)) {
            return false;
        } else {
            if ($aretrun = @unserialize($return)) {
                return $aretrun;
            } else {
                return $return;
            }
        }
    }

    //设置共享内存缓存
    public function setmemory($id, $data, $ttl = 0) {
        return $this->memory->set($id, $data, $ttl);
    }

    //设置共享内存缓存
    public function getmemory($id) {
        return $this->memory->get($id);
    }

//批量写入缓存
    public function sets(array $caches) {
        $return = array();
        foreach ($caches as $id => $value) {
            $this->set($id, $value);
        }
        return;
    }

    // ------------------------------------------------------------------------

    /**
     * Delete from Cache
     *
     * @param 	mixed		unique identifier of the item in the cache
     * @return 	boolean		true on success/false on failure
     */
    public function delete($id) {
        $return = false;
        if (trim($id)) {
            if ($this->memory->enable) {
                $return = $this->memory->rm($id);
            }
            if ($this->cache)
                $return = $this->cache->del_cache($id);

            return $return;
        }
    }

    public function delcache($id) {
        return $this->cache->del_cache($id);
    }

    // ------------------------------------------------------------------------

    /**
     * Clean the cache
     *
     * @return 	boolean		false on failure/true on success
     */
    public function clean() {
        $cdcaches = array();
        if (!empty($cannotdel)) {
            $cdcaches = $this->gets($cannotdel);
        }
        if ($this->memory->enable) {
            $this->memory->clear();
        }
        if ($this->cache)
            $this->cache->clean();
    }

    // ------------------------------------------------------------------------

    /**
     * Initialize
     *
     * Initialize class properties based on the configuration array.
     *
     * @param	array
     * @return 	void
     */
    private function _initialize() {
        $this->memory = new Memory();
        $config = Config::instance()->get("cache");
        if ($config['cache_type']) {
            $this->_adapter = $config['cache_type'];
            $className = trim("Cache_" . $this->_adapter);
            if ($className == "Cache_files") {
                $this->cache = new Cache_files();
            } else if ($className == "Cache_sql") {
                $this->cache = new Cache_sql();
            }
        }
        $tablelimit = Config::instance()->get('cache[tablelimit]');
        if ($tablelimit) {
            $this->table = new Cache_table();
        }
    }

    //判断缓存是否存在
    public function count() {
        return $this->cache ? $this->cache->count() : 0;
    }

    private function _cachehash() {
        $hash = mt_rand(100000, 999999);
        return $hash;
    }

    public static function & instance() {
        if (!self::$instances) {
            self::$instances = new self();
        }
        return self::$instances;
    }

//    //共享内存写入锁
//    private function lock() {
//        if ($this->memory->enable) {
//            return;
//        }
//        $lock = 1;
//        $this->lock = substr(md5(time()), 0, 5) . mt_rand(100, 999);
//        while (1) {
//            $lock = $this->memory->get($this->lock_key);
//            if (!$lock) {
//                break;
//            }
//            usleep(5);
//        }
//        //获取当前锁
//        $this->memory->set($this->lock_key, $this->lock, 0);
//        //echo "获取锁的成功 ，锁的值:{$this->lock}........\n";
//    }
//
//    //内存写入锁
//    private function unlock() {
//        if ($this->memory->enable) {
//            return;
//        }
//        if ($this->lock) {
//            $lock = $this->memory->get($this->lock_key);
//            if ($this->lock == $lock) {
//                $this->lock = "";
//                $reslut = $this->memory->rm($this->lock_key);
//                if (!$reslut) {
//                    Log::writelog("xxxxxxxxxxxxxxx删除共享内存锁的失败xxxxxxxxxxxxxxx");
//                }
//            }
//        }
//    }

    public function dellock() {
        $this->cache->del_cache($this->lock_key);
    }

}

/* End of file Cache.php */
/* Location: ./system/libraries/Cache/Cache.php */