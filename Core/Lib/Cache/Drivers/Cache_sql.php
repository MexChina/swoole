<?php

namespace Swoole\Core\Lib\Cache\Drivers;

use Swoole\Core\Server;
use Swoole\Core\Config;

class Cache_sql {
    
    protected $db = null;

    function __construct() {
        $server = & Server::instance();
        $this->db = new Database(Config::instance()->get("db[master]"));
        $this->db->connect();
    }

    function get_cache($key) {
        static $data = null;
        if (!isset($data[$key])) {
            $cache = $this->db->select('value, type, date, life')
                    ->where("key", "$key")
                    ->get($this->db->dbprefix('common_cache'))
                    ->row();

            if (!$cache) {
                return false;
            }
            if ($cache->life && ($cache->date < time() - $cache->life )) {
                $this->del_cache($key);
                return false;
            }
            $data[$key] = $cache->type ? unserialize(stripslashes($cache->value)) : stripslashes($cache->value);
        }
        return $data[$key];
    }

    function set_cache($key, $value, $life = 0) {
        $this->del_cache($key);
        if (is_array($value) || is_object($value)) {
            $value = addslashes(serialize($value));
            $type = 1;
        } else {
            $value = addslashes($value);
            $type = 0;
        }

        $data = array(
            'key' => $key,
            'value' => $value,
            'date' => time(),
            'life' => $life,
            'type' => $type
        );

        return $this->db->insert($this->db->dbprefix('common_cache'), $data);
    }

    function del_cache($key) {
        $return = $this->db->where('key', $key)
                ->delete($this->db->dbprefix('common_cache'));
        return $return;
    }

    function clean() {
        return $this->db->query("TRUNCATE TABLE " . $this->db->dbprefix('common_cache'));
    }

}
