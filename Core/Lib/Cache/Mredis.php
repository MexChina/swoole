<?php

namespace Swoole\Core\Lib\Cache;

use Swoole\Core\Log;
use \Redis;

/*
 * redis集群类，这个不需要redisCluster扩展  依赖redis扩展自己分槽
 */

/**
 * Description of mRedisCluster
 *
 * @author koly
 */
class Mredis {

    private $redis_server_num = 0; //连接redis集群时，redis的连接数量
    private $redis_master = [];
    private $redis_slave = [];
    public static $instance;

    const SLOT = 16384;

    private $cluster_config = []; //配置文件

    function __construct($config = []) {
        if (!empty($config)) {
            $this->cluster_config = $config;
        }
    }

    function connect() {
        if (empty($this->cluster_config)) {
            return false;
        }
        foreach ($this->cluster_config as $redis_id => $config) {
            list($master_host, $master_port) = explode(":", $config['master']);
            list($slave_host, $slave_port) = explode(":", $config['slave']);
            if ($master_host && $master_port) {
                $pconnect = isset($config['pconnect']) ? $config['pconnect'] : false;
                $timeout = isset($config['timeout']) ? $config['timeout'] : 5;
                $password = isset($config['password']) ? $config['password'] : null;
                $db = isset($config['db']) ? $config['db'] : null;
                $redis_client = $this->connect_redis($master_host, $master_port, $password, $timeout, $pconnect, $db);
                if (!$redis_client) {
                    Log::write_log("$master_host:$master_port connect failed ******");
                    continue;
                }
                $this->redis_master[$redis_id] = $redis_client;
                $this->redis_server_num ++;
            }
            if ($slave_host && $slave_port) {
                $pconnect = isset($config['pconnect']) ? $config['pconnect'] : false;
                $timeout = isset($config['timeout']) ? $config['timeout'] : 5;
                $password = isset($config['password']) ? $config['password'] : null;
                $db = isset($config['db']) ? $config['db'] : 0;
                $redis_client = $this->connect_redis($slave_host, $slave_port, $password, $timeout, $pconnect, $db);
                if (!$redis_client) {
                    Log::write_log("$slave_host:$slave_port connect failed ******");
                    continue;
                }
                $redis_client->rawCommand('readonly');
                $this->redis_slave[$redis_id] = $redis_client;
            }
        }
        if (count($this->cluster_config) != count($this->redis_master)) {
            $this->close();
            return false;
        } else {
            return TRUE;
        }
    }

    function close() {
        foreach ($this->redis_master as $client) {
            $client->close();
        }
        foreach ($this->redis_slave as $client) {
            $client->close();
        }
        $this->redis_master = [];
        $this->redis_slave = [];
    }

    public function hset($key, $field, $value) {
        $redis_server_id = $this->get_redis_server_id($key);
        return $this->redis_master[$redis_server_id]->hset($key, $field, $value);
    }

    public function hget($key, $field) {
        $redis_server_id = $this->get_redis_server_id($key);
        if ($this->redis_slave[$redis_server_id]) {
            $rs = $this->redis_slave[$redis_server_id]->hget($key, $field);
        } else {
            $rs = $this->redis_master[$redis_server_id]->hget($key, $field);
        }
        return $rs;
    }

    public function hmset($datas) {
        $len = count($datas);
        $start_time = microtime(TRUE);
        Log::write_log("start insert $len datas into redis ......");
        $pipes = $replies = $rs = $return = [];
        foreach ($datas as $dk => $data) {
            $key = $data['key'];
            $field = $data['field'];
            $value = $data['value'];
            try {
                $redis_server_id = $this->get_redis_server_id($key);
                if (!isset($pipes[$redis_server_id])) {
                    if (empty($this->redis_master[$redis_server_id])) {
                        Log::write_log("redis_server_id:$redis_server_id noe exists ......");
                    } else {
                        $pipes[$redis_server_id] = $this->redis_master[$redis_server_id]->multi(Redis::PIPELINE);
                    }
                }
                $pipes[$redis_server_id]->hset($key, $field, $value);
                $rs[$redis_server_id][] = $key;
            } catch (Exception $exc) {
                Log::write_log("redis_server_id:$redis_server_id pipe set error:" . $exc->getTraceAsString());
            }
            Log::write_log("redis hset $key-$field success");
        }
        foreach ($pipes as $key => $pipe) {
            try {
                $replies = $pipe->exec();
                $tmp = array_combine($rs[$key], $replies);
                $return = array_merge($return, array_keys($tmp, "", TRUE));
            } catch (Exception $exc) {
                Log::write_log("redis_server_id:$redis_server_id multi exec error:" . $exc->getTraceAsString());
            }
        }
        $end_time = microtime(TRUE);
        Log::write_log("success insert $len datas into redis, use " . intval(($end_time - $start_time) * 1000) . "ms ......");
        if (empty($return)) {
            return TRUE;
        }
        return $return;
    }

    public function hmgetall($datas){
        $len = count($datas);
        $start_time = microtime(TRUE);
        Log::write_log("start get $len datas from redis ......");
        $pipes = $replies = $rs = $return = [];
        foreach ($datas as $dk => $key) {
            try {
                $redis_server_id = $this->get_redis_server_id($key);
                if (isset($pipes[$redis_server_id])) {
                    $pipes[$redis_server_id]->hGetAll($key);
                    $rs[$redis_server_id][] = $key;
                } elseif (!empty($this->redis_slave[$redis_server_id])) {
                    $pipes[$redis_server_id] = $this->redis_slave[$redis_server_id]->multi(Redis::PIPELINE);
                    $pipes[$redis_server_id]->hGetAll($key);
                    $rs[$redis_server_id][] = $key;
                } elseif (!empty($this->redis_master[$redis_server_id])) {
                    $pipes[$redis_server_id] = $this->redis_master[$redis_server_id]->multi(Redis::PIPELINE);
                    $pipes[$redis_server_id]->hGetAll($key);
                    $rs[$redis_server_id][] = $key;
                } else {
                    Log::write_log("redis_server_id:$redis_server_id noe exists ......");
                }
            } catch (Exception $exc) {
                Log::write_log("redis_server_id:$redis_server_id" . $exc->getTraceAsString());
            }
        }
        foreach ($pipes as $key => $pipe) {
            @$replies = $pipe->exec();
            //var_export(@$replies);
            $return = array_merge($return, array_combine($rs[$key], $replies));
        }
        $end_time = microtime(TRUE);
        Log::write_log("success get $len datas from redis, use " . intval(($end_time - $start_time) * 1000) . "ms ......");
        return $return;
    }

    public function hmget($datas) {
        $len = count($datas);
        $start_time = microtime(TRUE);
        Log::write_log("start get $len datas from redis ......");
        $pipes = $replies = $rs = $return = [];
//        foreach ($this->redis_master as $redis_server_id => $redis) {
//            $pipes[$redis_server_id] = $redis->multi(Redis::PIPELINE);
//        }
        foreach ($datas as $dk => $key) {
            try {
                $redis_server_id = $this->get_redis_server_id($key);
                if (isset($pipes[$redis_server_id])) {
                    $pipes[$redis_server_id]->hvals($key);
                    $rs[$redis_server_id][] = $key;
                } elseif (!empty($this->redis_slave[$redis_server_id])) {
                    $pipes[$redis_server_id] = $this->redis_slave[$redis_server_id]->multi(Redis::PIPELINE);
                    $pipes[$redis_server_id]->hvals($key);
                    $rs[$redis_server_id][] = $key;
                } elseif (!empty($this->redis_master[$redis_server_id])) {
                    $pipes[$redis_server_id] = $this->redis_master[$redis_server_id]->multi(Redis::PIPELINE);
                    $pipes[$redis_server_id]->hvals($key);
                    $rs[$redis_server_id][] = $key;
                } else {
                    Log::write_log("redis_server_id:$redis_server_id noe exists ......");
                }
            } catch (Exception $exc) {
                Log::write_log("redis_server_id:$redis_server_id" . $exc->getTraceAsString());
            }
        }
        foreach ($pipes as $key => $pipe) {
            @$replies = $pipe->exec();
            //var_export(@$replies);
            $return = array_merge($return, array_combine($rs[$key], $replies));
        }
        $end_time = microtime(TRUE);
        Log::write_log("success get $len datas from redis, use " . intval(($end_time - $start_time) * 1000) . "ms ......");
        return $return;
    }

    public function get($key) {
        $redis_server_id = $this->get_redis_server_id($key);
        if ($this->redis_slave[$redis_server_id]) {
            $rs = $this->redis_slave[$redis_server_id]->get($key);
        } else {
            $rs = $this->redis_master[$redis_server_id]->get($key);
        }
        return $rs;
    }

    public function set($key, $value) {
        $redis_server_id = $this->get_redis_server_id($key);
        return $this->redis_master[$redis_server_id]->set($key, $value);
    }

    public function mset($datas) {
        $pipes = $replies = $return = $rs = [];
        $len = count($datas);
        $start_time = microtime(TRUE);
        Log::write_log("start insert $len datas into redis ......");
        foreach ($datas as $key => $value) {
            try {
                $redis_server_id = $this->get_redis_server_id($key);
                if (isset($pipes[$redis_server_id])) {
                    $pipes[$redis_server_id]->set($key, $value);
                    $rs[$redis_server_id][] = $key;
                } elseif (!empty($this->redis_master[$redis_server_id])) {
                    $pipes[$redis_server_id] = $this->redis_master[$redis_server_id]->multi(Redis::PIPELINE);
                    $pipes[$redis_server_id]->set($key, $value);
                    $rs[$redis_server_id][] = $key;
                } else {
                    Log::write_log("redis_server_id:$redis_server_id noe exists ......");
                }
            } catch (Exception $exc) {
                Log::write_log("redis_server_id:$redis_server_id" . $exc->getTraceAsString());
            }
        }
        foreach ($pipes as $key => $pipe) {
            $replies = $pipe->exec();
            $tmp = array_combine($rs[$key], $replies);
            $return = array_merge($return, array_keys($tmp, false, TRUE));
        }
        $end_time = microtime(TRUE);
        Log::write_log("success insert $len datas into redis, use " . intval(($end_time - $start_time) * 1000) . "ms ......");
        return empty($return) ? true : $return;
    }

    public function mget($datas) {
        $len = count($datas);
        $start_time = microtime(TRUE);
        Log::write_log("start get $len datas from redis ......");
        $pipes = $replies = $rs = $return = [];
        foreach ($datas as $key) {
            try {
                $redis_server_id = $this->get_redis_server_id($key);
                if (isset($pipes[$redis_server_id])) {
                    $pipes[$redis_server_id]->get($key);
                    $rs[$redis_server_id][] = $key;
                } elseif (!empty($this->redis_slave[$redis_server_id])) {
                    $pipes[$redis_server_id] = $this->redis_slave[$redis_server_id]->multi(Redis::PIPELINE);
                    $pipes[$redis_server_id]->get($key);
                    $rs[$redis_server_id][] = $key;
                } elseif (!empty($this->redis_master[$redis_server_id])) {
                    $pipes[$redis_server_id] = $this->redis_master[$redis_server_id]->multi(Redis::PIPELINE);
                    $pipes[$redis_server_id]->get($key);
                    $rs[$redis_server_id][] = $key;
                } else {
                    Log::write_log("redis_server_id:$redis_server_id noe exists ......");
                }
            } catch (Exception $exc) {
                Log::write_log("redis_server_id:$redis_server_id" . $exc->getTraceAsString());
            }
        }
        foreach ($pipes as $key => $pipe) {
            $replies = $pipe->exec();
            $return = array_merge($return, array_combine($rs[$key], $replies));
        }
        $end_time = microtime(TRUE);
        Log::write_log("success get $len datas from redis, use " . intval(($end_time - $start_time) * 1000) . "ms ......");
        return $return;
    }

    function dbsize() {
        $size = 0;
        foreach ($this->redis_master as $client) {
            $size += $client->dbsize();
        }
        return $size;
    }

    function clean_db() {
        $rs = true;
        foreach ($this->redis_master as $redis_server_id => $redis) {
            $rs = $rs && $redis->flushdb();
        }
        return $rs;
    }

    function info() {
        $rs = [];
        foreach ($this->redis_master as $redis_server_id => $redis) {
            $rs[$redis_server_id] = $redis->info();
        }
        return $rs;
    }

    function clean_all() {
        $rs = true;
        foreach ($this->redis_master as $redis_server_id => $redis) {
            $rs = $rs && $pipes[$redis_server_id] = $redis->flushall();
        }
        return $rs;
    }

    private function connect_redis($host, $port, $password = '', $timeout = 5, $pconnect = false, $db = null) {
        try {
            $redis_client = new \Redis;
            $func = $pconnect ? 'pconnect' : 'connect';
            $rs = $redis_client->$func($host, $port, $timeout);
            if ($password) {
                $rs = $redis_client->auth($password);
                if (!$rs) {
                    return false;
                }
            }
            if ($db !== null) {
                $rs = $redis_client->select(intval($db));
            }
            if (!$rs) {
                return false;
            }
        } catch (Exception $exc) {
            Log::writelog("host:$host,port:$port" . $exc->getTraceAsString());
            return false;
        }
        return $redis_client;
    }

    private function crc16(&$ptr) {
        $crc_table = array(
            0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50a5, 0x60c6, 0x70e7,
            0x8108, 0x9129, 0xa14a, 0xb16b, 0xc18c, 0xd1ad, 0xe1ce, 0xf1ef,
            0x1231, 0x0210, 0x3273, 0x2252, 0x52b5, 0x4294, 0x72f7, 0x62d6,
            0x9339, 0x8318, 0xb37b, 0xa35a, 0xd3bd, 0xc39c, 0xf3ff, 0xe3de,
            0x2462, 0x3443, 0x0420, 0x1401, 0x64e6, 0x74c7, 0x44a4, 0x5485,
            0xa56a, 0xb54b, 0x8528, 0x9509, 0xe5ee, 0xf5cf, 0xc5ac, 0xd58d,
            0x3653, 0x2672, 0x1611, 0x0630, 0x76d7, 0x66f6, 0x5695, 0x46b4,
            0xb75b, 0xa77a, 0x9719, 0x8738, 0xf7df, 0xe7fe, 0xd79d, 0xc7bc,
            0x48c4, 0x58e5, 0x6886, 0x78a7, 0x0840, 0x1861, 0x2802, 0x3823,
            0xc9cc, 0xd9ed, 0xe98e, 0xf9af, 0x8948, 0x9969, 0xa90a, 0xb92b,
            0x5af5, 0x4ad4, 0x7ab7, 0x6a96, 0x1a71, 0x0a50, 0x3a33, 0x2a12,
            0xdbfd, 0xcbdc, 0xfbbf, 0xeb9e, 0x9b79, 0x8b58, 0xbb3b, 0xab1a,
            0x6ca6, 0x7c87, 0x4ce4, 0x5cc5, 0x2c22, 0x3c03, 0x0c60, 0x1c41,
            0xedae, 0xfd8f, 0xcdec, 0xddcd, 0xad2a, 0xbd0b, 0x8d68, 0x9d49,
            0x7e97, 0x6eb6, 0x5ed5, 0x4ef4, 0x3e13, 0x2e32, 0x1e51, 0x0e70,
            0xff9f, 0xefbe, 0xdfdd, 0xcffc, 0xbf1b, 0xaf3a, 0x9f59, 0x8f78,
            0x9188, 0x81a9, 0xb1ca, 0xa1eb, 0xd10c, 0xc12d, 0xf14e, 0xe16f,
            0x1080, 0x00a1, 0x30c2, 0x20e3, 0x5004, 0x4025, 0x7046, 0x6067,
            0x83b9, 0x9398, 0xa3fb, 0xb3da, 0xc33d, 0xd31c, 0xe37f, 0xf35e,
            0x02b1, 0x1290, 0x22f3, 0x32d2, 0x4235, 0x5214, 0x6277, 0x7256,
            0xb5ea, 0xa5cb, 0x95a8, 0x8589, 0xf56e, 0xe54f, 0xd52c, 0xc50d,
            0x34e2, 0x24c3, 0x14a0, 0x0481, 0x7466, 0x6447, 0x5424, 0x4405,
            0xa7db, 0xb7fa, 0x8799, 0x97b8, 0xe75f, 0xf77e, 0xc71d, 0xd73c,
            0x26d3, 0x36f2, 0x0691, 0x16b0, 0x6657, 0x7676, 0x4615, 0x5634,
            0xd94c, 0xc96d, 0xf90e, 0xe92f, 0x99c8, 0x89e9, 0xb98a, 0xa9ab,
            0x5844, 0x4865, 0x7806, 0x6827, 0x18c0, 0x08e1, 0x3882, 0x28a3,
            0xcb7d, 0xdb5c, 0xeb3f, 0xfb1e, 0x8bf9, 0x9bd8, 0xabbb, 0xbb9a,
            0x4a75, 0x5a54, 0x6a37, 0x7a16, 0x0af1, 0x1ad0, 0x2ab3, 0x3a92,
            0xfd2e, 0xed0f, 0xdd6c, 0xcd4d, 0xbdaa, 0xad8b, 0x9de8, 0x8dc9,
            0x7c26, 0x6c07, 0x5c64, 0x4c45, 0x3ca2, 0x2c83, 0x1ce0, 0x0cc1,
            0xef1f, 0xff3e, 0xcf5d, 0xdf7c, 0xaf9b, 0xbfba, 0x8fd9, 0x9ff8,
            0x6e17, 0x7e36, 0x4e55, 0x5e74, 0x2e93, 0x3eb2, 0x0ed1, 0x1ef0
        );
        $crc = 0x0000;
        for ($i = 0; $i < strlen($ptr); $i++)
            $crc = $crc_table[(($crc >> 8) ^ ord($ptr[$i]))] ^ (($crc << 8) & 0x00FFFF);
        return $crc;
    }

    private function get_redis_server_id($key) {
        $slot_id = ($this->crc16($key) % self::SLOT);
        //echo $slot_id, PHP_EOL;
        $redis_id = floor($slot_id / ceil(self::SLOT / $this->redis_server_num));
        return $redis_id;
    }

    public function __call($name, $arguments) {
        switch ($name) {
            case 'rawCommand':
                $redis = $this->redis_master[array_rand($this->redis_master)];
                $rs = call_user_func_array(array($redis, 'rawCommand'), $arguments);
                break;
            case 'getOption':
                $redis = $this->redis_master[array_rand($this->redis_master)];
                $rs = call_user_func_array(array($redis, 'getOption'), $arguments);
                break;
            default:
                $key = $arguments[0];
                $redis_server_id = $this->get_redis_server_id($key);
                $rs = call_user_func_array(array($this->redis_master[$redis_server_id], $name), $arguments);
                break;
        }
        return $rs;
    }

}
