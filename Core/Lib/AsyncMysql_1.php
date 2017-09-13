<?php

namespace Swoole\Core\Lib;

use Swoole\Core\Log;
use Swoole\Core\Lib\Database;
use \SplQueue;
use \mysqli;

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
    private $wait_pool_num = 1000; //等待执行的sql最大数量
    private $wait_function_pool; //等待执行sql的回调函数 
    private $is_close = false; //数据库连接是否要求被关闭
    private $exdb_num = 0; //异常退出的数据库连接数
    public $db; //当前用来执行查询的mysql连接

    function __construct(array $mysql_config, $pool_num = 0) {
        $this->mysql_config = $mysql_config;
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

    public function query($sql, $function = NULL) {
        if ($this->exdb_num > 0) {
            $result = $this->create_rsync_db($this->exdb_num);
            if (!$result) {
                return false;
            }
            $this->exdb_num = 0;
        }
        if ($sql) {
            return $this->handle($sql, $function);
        }
    }

    private function handle($sql = null, $function = NULL) {
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
            $dbkey = array_rand($this->dbs);
            $this->db = $this->dbs[$dbkey];
            unset($this->dbs[$dbkey]);
            $result = swoole_mysql_query($db, $sql, function(mysqli $db, $result) use($function, $sql) {
                //SQL执行失败了
                if ($result === false) {
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
                }
                //执行成功，update/delete/insert语句，没有结果集
                elseif ($result === true) {
                    $this->dbs[] = $db;
                    if (!empty($function)) {
                        $new_result['affected_rows'] = $db->_affected_rows;
                        $new_result['insert_id'] = $db->_insert_id;
                        call_user_func($function, $new_result);
                    }
                }
                //执行成功，$r是结果集数组
                else {
                    $this->dbs[] = $db;
                    if (!empty($function)) {
                        call_user_func($function, $result);
                    }
                }
                unset($db);
                unset($function);
                unset($sql);
                unset($result);
                $this->handle();
            });
            return $result;
        }
    }

    private function create_rsync_db($num = 0) {
        $num = $num ? $num : $this->pool_num;
        $i = 0;
        while ($i < $num) {
            $mysqli = $this->create_mysql_connect();
            if (!$mysqli) {
                return FALSE;
            } else {
                $this->dbs[] = $mysqli;
                $i++;
            }
        }
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
        if ($this->isfree()) {
            $this->del_rsync_dbs();
            unset($this);
        } else {
            $this->is_close = TRUE; //标记为需要关闭的状态
        }
    }

    //异步连接池是否空闲
    public function isfree() {
        if (count($this->dbs) == $this->pool_num && count($this->wait_pool) < 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    //获取缓存池的缓存数量
    public function get_pool_num() {
        return count($this->wait_pool);
    }

    //删除存储数据库异步链接池
    private function del_rsync_dbs() {
        Log::write_log("close AsyncMysql client");
        foreach ($this->dbs as $tmp_dbsock => $db) {
            $db->close();
        }
        $this->dbs = array();
        $this->is_close = FALSE;
    }

    public function table($table_name) {
        return isset($this->mysql_config['pre']) ? ($this->mysql_config['pre'] . $table_name) : $table_name;
    }

    private function create_mysql_connect() {
        $mysqli = mysqli_init();
        if (!$mysqli) {
            Log::write_error('mysqli_init failed');
            return false;
        }
        if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3)) {
            Log::write_error('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
            return false;
        }

        $host = $this->mysql_config["host"];
        $prot = $this->mysql_config["port"];
        $username = $this->mysql_config["user"];
        $password = $this->mysql_config["passwd"];
        $dbname = $this->mysql_config["name"];
        if (!$host || !$prot || !$username || !$password || !$dbname) {
            Log::write_error("error:mysql config  exception\thost:$host\tprot:$prot\tusername:$username\tpassword:$password\tdbname:$dbname");
            return false;
        }
        if (!$mysqli->real_connect($host, $username, $password, $dbname, $prot)) {
            Log::write_error('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
            return false;
        }
        return $mysqli;
    }

    /*
      function mysql_callback($dbsock) {
      if (!empty($this->dbs[$dbsock])) {
      $db = $this->dbs[$dbsock]->db;
      Log::writelog("[sock:$dbsock] errno:{$db->errno}, error:{$db->error}");
      $this->del_swoole_event($dbsock);
      unset($this->dbs[$dbsock]);
      $newdbsock = $this->create_rsync_db(1);
      Log::writelog("[sock:$dbsock]async mysql has gone away, create new connect [sock:$newdbsock]");
      return;
      } elseif (empty($this->work_dbs[$dbsock])) {
      Log::writelog("[sock:$dbsock] msg:not in work dbs pool");
      $this->del_swoole_event($dbsock);
      return;
      }

      $function = @$this->work_dbs[$dbsock]["function"];
      if ($result = $this->work_dbs[$dbsock]["db"]->reap_async_query()) {
      if (!empty($function)) {
      call_user_func($function, $result);
      }
      if (get_class($result) === "mysqli_result") {
      @$result->free();
      }
      unset($result);
      } else {
      $errno = mysqli_errno($this->work_dbs[$dbsock]["db"]->db);
      $error = mysqli_error($this->work_dbs[$dbsock]["db"]->db);
      Log::writelog("Errno:$errno,Error:$error");
      //离线重连
      if ($errno == 2013 || $errno == 2006 || !$error) {
      $this->del_swoole_event($dbsock);
      $this->create_rsync_db(1);
      $this->handle($this->work_dbs[$dbsock]["sql"], $function);
      unset($this->work_dbs[$dbsock]);
      return;
      } else if ($error) {
      //记录异常sql
      $writelogresult = File::write_file(SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . "/log/error.sql", ($this->work_dbs[$dbsock]["sql"] . "\n"), "a");
      }
      }
      $this->dbs[$dbsock] = $this->work_dbs[$dbsock]["db"];
      unset($this->work_dbs[$dbsock]);
      $this->handle();
      $this->close($this->is_close);
      }

      //检查是否超过设置的mysql连接数
      private function check_savedb_connect_num() {
      $db_connect_count = count($this->savedb) + count($this->work_dbs);
      //当工作队列中的mysql连接数大于设置的最大连接数时，开始阻塞
      if (count($this->work_dbs) > $this->max_savadb_connect) {
      $this->wait_status = 1;
      Log::writelog("[db:{$this->db} position:{$this->docounts}] db connect too many {$db_connect_count}......");
      return true;
      } else {
      $this->wait_status = 0;
      return false;
      }
      }


      //删除存储数据库异步链接池
      private function del_rsync_dbs() {
      foreach ($this->dbs as $tmp_dbsock => $db) {
      $del_result = $this->del_swoole_event($tmp_dbsock);
      $db->close();
      unset($this->dbs[$tmp_dbsock]);
      }
      $this->dbs = array();
      }

      private function del_swoole_event($sock) {
      if (!$sock) {
      Log::writelog("scok enabled [sock:$sock] ......");
      return;
      }
      if (!empty($this->event_poll[$sock])) {
      $result = swoole_event_del($sock);
      if (!$result) {
      Log::writelog("[sock:{$sock}] delete failed ......");
      } else {
      unset($this->event_poll[$sock]);
      }
      } else {
      Log::writelog("[sock:{$sock}] event poll not exists this scok ......");
      }
      }

      private function add_swoole_event($sock, $function) {
      if (!$sock) {
      Log::writelog("scok enabled [sock:$sock] ......");
      return;
      }
      if (empty($this->event_poll[$sock])) {
      $result = swoole_event_add($sock, $function);
      if (!$result) {
      Log::writelog("[sock:{$sock}] add failed ......");
      } else {
      $this->event_poll[$sock] = $function;
      }
      } else {
      Log::writelog("[sock:{$sock}] event poll already exists this scok ......");
      }
      }
     */
}
