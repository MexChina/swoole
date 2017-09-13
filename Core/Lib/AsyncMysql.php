<?php

namespace Swoole\Core\Lib;

use Swoole\Core\Log;
use Swoole\Core\Lib\Database\MySQLi;
use Swoole\Core\Lib\Database\MySQLiRecord;
use \SplQueue;

/**
 * 
 * 异步mysql类
 * 
 */
class AsyncMysql {

    private $dbs = []; //异步mysql连接池
    private $mysql_config = []; //mysql配置文件
    private $pool_num = 10; //默认连接池数量
    private $wait_pool; //等待执行的sql
    private $wait_pool_num = 1000; //sql 缓存池
    private $wait_function_pool; //等待执行sql的回调函数 
    private $is_close = false; //数据库连接是否要求被关闭
    private $exdb_num = 0; //异常退出的数据库连接数
    private $timeout = 0;
    public $db; //当前用来执行查询的mysql连接

    function __construct(array $mysql_config, $pool_num = 0, $timeout = 5) {
        $this->mysql_config = $mysql_config;
        $this->timeout = $timeout;
        $this->pool_num = $pool_num ? : $this->pool_num;
        $this->create_rsync_db();
        $this->wait_pool = new SplQueue;
        $this->wait_function_pool = new SplQueue;
    }

    function __destruct() {
        foreach ($this->dbs as $db) {
            if (is_object($db)) {
                $db->close();
            }
        }
        unset($this->wait_pool_num);
        unset($this->wait_function_pool);
    }

    public function query($sql, $function = NULL, $resultmode = MYSQLI_STORE_RESULT) {
        if ($this->exdb_num > 0) {
            $result = $this->create_rsync_db($this->exdb_num);
            if (!$result) {
                return false;
            }
            $this->exdb_num = 0;
        }
        if ($sql) {
            return $this->handle($sql, $function, $resultmode);
        }
    }

    private function handle($sql = null, $function = NULL, $resultmode = MYSQLI_STORE_RESULT) {
        if (!$sql && count($this->wait_pool) > 0) {
            $result = $this->sql_shift();
            if (!$result) {
                return;
            }
            $sql = $result["sql"];
            $function = $result["function"];
            unset($result);
        } elseif (!$sql && $this->is_close) {
            $this->close();
        }
        if ($sql) {
            if (count($this->dbs) < 1) {
                $result = $this->sql_push($sql, $function);
                return $result;
            } else {
                $db_key = array_rand($this->dbs);
            }
            //准备下一次查询的db连接
            $db = $this->db;
            $this->db = $this->dbs[$db_key];
            unset($this->dbs[$db_key]);
            $r = $db->async_query($sql, function(MySQLiRecord $result, $db) use($function, $resultmode) {
                //SQL执行失败了
                if ($result->result === false) {
                    Log::write_log("mysql_error:{$db->errno}\tmysql_errno:{$db->error}");
                    Log::write_log("mysql_error:{$db->_errno}\tmysql_errno:{$db->_error}");
                    if ($db->_errno == 2013 || $db->_errno == 2006) {
                        $this->sql_unshift($sql, $function);
                        $this->exdb_num ++;
                    } else {
                        $this->dbs[] = $db;
                        Log::write_error(($sql . "\n"), "sql_error");
                        if (!empty($function)) {
                            call_user_func($function, $result);
                        }
                    }
                } else {
                    call_user_func($function, $result->result);
                    if ($resultmode == MYSQLI_ASYNC && !empty($result)) {
                        $result->free();
                    }
                    $this->dbs[$db->sock] = $db;
                    $this->handle();
                    unset($db);
                    unset($function);
                    unset($result);
                    unset($resultmode);
                }
            }, $resultmode);
            return $r;
        }
    }

    private function create_rsync_db($num = 0) {
        $num = $num ? $num : $this->pool_num;
        $i = 0;
        while ($i < $num) {
            $mysqli = new MySQLi($this->mysql_config);
            if ($this->timeout > 0) {
                $mysqli->set_async_timeout($this->timeout);
            }
            if (!$mysqli) {
                return FALSE;
            } else {
                $this->dbs[$mysqli->sock] = $mysqli;
                $i++;
            }
        }
        //准备下一次查询的db连接
        $dbkey = array_rand($this->dbs);
        $this->db = $this->dbs[$dbkey];
        unset($this->dbs[$dbkey]);
        return TRUE;
    }

    private function sql_push($sql, $function = NULL) {
        //Log::write_log("sql push");
        if (count($this->wait_pool) < $this->wait_pool_num) {
            $result1 = $this->wait_pool->push($sql);
            $result2 = $this->wait_function_pool->push($function);
            if (!$result1 || !$result2) {
                Log::write_error("push sql or function error");
                return false;
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }

    private function sql_unshift($sql, $function = NULL) {
        //Log::write_log("sql unshift");
        $result1 = $this->wait_pool->unshift($sql);
        $result2 = $this->wait_function_pool->unshift($function);
        if (!$result1 || !$result2) {
            Log::write_error("sql or function error:");
        }
        return TRUE;
    }

    private function sql_shift() {
        //Log::write_log("sql shift");
        if (count($this->wait_pool) > 0) {
            $sql = $this->wait_pool->shift();
            $function = $this->wait_function_pool->shift();
            return ["sql" => $sql, "function" => $function];
        } else {
            return FALSE;
        }
    }

    //关闭
    public function close() {
        if (count($this->wait_pool) < 1 && count($this->dbs) == ($this->pool_num - 1)) {
            foreach ($this->dbs as $sock => $db) {
                $db->close();
            }
        } else {
            $this->is_close = true;
        }
    }

    //异步连接池是否空闲
    public function isfree() {
        if (count($this->dbs) == ($this->pool_num - 1) && count($this->wait_pool) < 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function __get($name) {
        return $this->db->$name;
    }

    function __call($method, $args = array()) {
        return call_user_func_array(array($this->db, $method), $args);
    }

}
