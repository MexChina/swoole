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
    protected $_backup_driver;
    public $memory = NULL;
    public static $instances;
    public $cache;
    public $table;

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
        if ($this->table) {
            $result = $this->get_table($id);
            if ($result !== FALSE) {
                return $result;
            }
        }
        if ($this->memory->enable) {
            $result = $this->get_memory($id);
            if ($result !== FALSE) {
                return $result;
            }
        }
        if ($this->cache) {
            $result = $this->get_cache($id);
            if ($result !== FALSE) {
                return $result;
            }
        }
        return false;
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
        $result_table = $result_memory = $result_cache = FALSE;
        if ($this->table) {
            $result_table = $this->set_table($id, $data, $ttl);
        }
        if ($this->memory->enable) {
            $result_memory = $this->set_memory($id, $data, $ttl);
        }
        if ($this->cache) {
            $result_cache = $this->set_cache($id, $data, $ttl);
        }
        if (!$result_table && !$result_memory && !$result_cache) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    //设置本机内存缓存
    public function set_table($id, $data, $ttl = 0) {
        if (is_array($data)) {
            $data = serialize($data);
        }
        $this->table->set_cache($id, $data, $ttl);
    }

    //获取本机内存缓存
    public function get_table($id) {
        $return = $this->table->get_cache($id);
        if ($return === FALSE) {
            return false;
        } else {
            if ($aretrun = @unserialize($return)) {
                return $aretrun;
            } else {
                return $return;
            }
        }
    }

    //设置持久内存缓存
    public function set_cache($id, $data, $ttl = 0) {
        if (is_array($data)) {
            $data = serialize($data);
        }
        $this->cache->set_cache($id, $data, $ttl);
    }

    //获取持久内存缓存
    public function get_cache($id) {
        $return = $this->cache->get_cache($id);
        if ($return === FALSE) {
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
    public function set_memory($id, $data, $ttl = 0) {
        return $this->memory->set($id, $data, $ttl);
    }

    //设置共享内存缓存
    public function get_memory($id) {
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

    public function dellock() {
        $this->cache->del_cache($this->lock_key);
    }

}

/* End of file Cache.php */
/* Location: ./system/libraries/Cache/Cache.php */