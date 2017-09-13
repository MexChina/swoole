<?php

namespace Swoole\Core\Lib\Database;

use Swoole\Core\Lib\IDatabase;
use Swoole\Core\Lib\IDbRecord;
use Swoole\Core\Log;
use Swoole\Core\Helper\File;
use Swoole\Core\Helper\System;

/**
 * MySQL数据库封装类
 *
 * @package SwooleExtend
 * @author  Tianfeng.Han
 *
 */
class MySQLi extends \mysqli implements IDatabase {

    const DEFAULT_PORT = 3306;

    public $debug = false;
    public $conn = null;
    public $config;
    public $errno;
    public $error;
    public $sock = null;
    private $process;
    private $isclose = false; //标记异步处理完毕后关闭连接
    private $mysqli_use_result = false; //返回结果集的方式
    public $isbusy = false; //该连接是否繁忙,主要针对异步查询
    public $back_function = null; //异步查询的回调函数
    public $async_query_sql = ""; //异步查询sql缓存
    public static $MYSQLI_DBS = [];

    public static function mysqli_async_callback($sock) {
        @$db = self::$MYSQLI_DBS[$sock];
        if (!$db) {
            return false;
        }
        $links[] = $db;
        if (!mysqli_poll($links, $links, $links, 0, 1000)) {
            return;
        }
        $function = $db->back_function;
        $async_query_sql = $db->async_query_sql;
        $db->back_function = NULL;
        $db->async_query_sql = "";
        try {
            $r = swoole_event_del($db->sock);
        } catch (Error $e) {
            echo "Exception: {$e->getMessage()}\n";
        }
        System::exec_time();
        if (@$result = $db->reap_async_query()) {
            if ($function) {
                call_user_func($function, new MySQLiRecord($result), $db);
            }
            @mysqli_free_result($result);
        } else {
            Log::write_error("mysqli error($db->errno):$db->error \n");
            if ($db->errno == 2013 || $db->errno == 2006 || $db->errno == 1053) {
                $r = $db->checkConnection();
                if ($r === true) {
                    $db->async_query($async_query_sql, $function);
                }
            } else {
                $db->cache_error_sql($async_query_sql);
                if ($function) {
                    call_user_func($function, new MySQLiRecord($result), $db);
                }
            }
        }
        if ($db->isclose) {
            $db->close();
        }
        unset($result);
        unset($function);
        unset($async_query_sql);
        unset($links);
        unset($db);
        //echo "9 memory used " . System::get_used_memory() . "\n";
    }

    function __construct($db_config) {
        if (empty($db_config['port'])) {
            $db_config['port'] = self::DEFAULT_PORT;
        }
        $this->config = $db_config;
        $this->sock = null;
        $this->connect();
    }

    function __destruct() {
        unset(self::$MYSQLI_DBS[$this->sock]);
        unset($this->back_function);
    }

    function lastInsertId() {
        return $this->insert_id;
    }

    function connect($host = null, $user = null, $password = null, $database = null, $port = null, $socket = null) {
        if ($this->sock) {
            return TRUE;
        } else {
            $db_config = $this->config;
            if (!empty($db_config['persistent'])) {
                $db_config['host'] = 'p:' . $db_config['host'];
            }
            if (isset($db_config['passwd'])) {
                $db_config['password'] = $db_config['passwd'];
            }
            if (isset($db_config['dbname'])) {
                $db_config['name'] = $db_config['dbname'];
            }
            $count = 0;
            while (1) {
                $count ++;
                parent::connect($db_config['host'], $db_config['user'], $db_config['password'], $db_config['name'], $db_config['port']);
                if (mysqli_connect_errno()) {
                    Log::writelog("第 {$count} 次连接mysql(dbhost:{$db_config['host']},dbport:{$db_config['port']},dbpwd:{$db_config['password']},dbuser:{$db_config['user']},dbname:{$db_config['name']})失败: " . mysqli_connect_error());
                    if ($count >= 3) {
                        return false;
                    }
                    sleep(rand(1, 5));
                } else {
                    break;
                }
            }
            if (!empty($db_config['charset'])) {
                $this->set_charset($db_config['charset']);
            }
            $this->sock = swoole_get_mysqli_sock($this);
            self::$MYSQLI_DBS[$this->sock] = $this;
            return true;
        }
    }

    /**
     * 过滤特殊字符
     *
     * @param $value
     *
     * @return string
     */
    function quote($value) {
        return $this->real_escape_string($value);
    }

    protected function errorMessage($sql) {
        $msg = $this->error . "$sql";
        $msg .= "Server: {$this->config['host']}:{$this->config['port']}";
        $msg .= "Errno: {$this->errno}";
        return $msg;
    }

    /**
     * 执行一个SQL语句
     *
     * @param string $sql 执行的SQL语句
     *
     * @return MySQLiRecord | false
     */
    public function query($sql, $resultmode = MYSQLI_STORE_RESULT) {
        //$sql = $this->quote($sql);
        $result = false;
        //重试三次
        $r = true;
        for ($i = 0; $i < 2; $i++) {
            try {
                $result = parent::query($sql, $resultmode);
                if (empty($result)) {
                    if ($this->errno == 2013 || $this->errno == 2006 || $this->errno == 1053) {
                        $r = $this->checkConnection();
                        if ($r === true) {
                            continue;
                        }
                    }
                }
                break;
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }

        if ($resultmode < MYSQLI_ASYNC) {
            if (!$result) {
                $this->cache_error_sql($sql);
            }
            return new MySQLiRecord($result);
        } else {
            $this->isbusy = true;
        }
        if ($i >= 2) {
            return false;
        } else {
            return TRUE;
        }
    }

    /**
     * 异步执行mysql查询
     */
    public function async_query($sql, $function, $resultmode = MYSQLI_STORE_RESULT) {
        if ($this->isbusy) {
            Log::writelog("mysqli error:this mysql connect is busy, please retry later");
            return false;
        }
        $this->back_function = $function;
        $this->async_query_sql = $sql ;
        if ($resultmode === MYSQLI_USE_RESULT) {
            $this->mysqli_use_result = true;
        }
//        try {
//            $r = swoole_event_add($this->sock, "Swoole\Core\Lib\Database\MySQLi::mysqli_async_callback");
//        } catch (Error $e) {
//            echo "Exception: {$e->getMessage()}\n";
//        }
        $r = true;
        if ($r) {
            return $this->query($sql, MYSQLI_ASYNC | $resultmode);
        } else {
            Log::writelog("mysqli error: swoole_event_add return false");
            return false;
        }
    }

    /**
     * 异步查询结果获取, 异步查询的结果获取这一步将会把所有的结果集数据拷贝到php内存中，对于数据较大的这里将会阻塞
     */
    public function reap_async_query() {
        $this->isbusy = false;
        return parent::reap_async_query();
    }

    /**
     * 检查数据库连接,是否有效，无效则重新建立
     */
    public function checkConnection() {
        if (!@$this->ping()) {
            Log::write_log("reconnect mysql");
            $this->close();
            return $this->connect();
        } else {
            return true;
        }
    }

    public function setprocess($process) {
        $this->process = $process;
    }

    /**
     * 返回上一个Insert语句的自增主键ID
     * @return int
     */
    function Insert_ID() {
        return $this->insert_id;
    }

    /**
     * 关闭连接
     *
     * @see libs/system/IDatabase#close()
     */
    function close() {
        if ($this->isbusy) {
            //标记为关闭
            $this->isclose = TRUE;
        } else {
            unset(self::$MYSQLI_DBS[$this->sock]);
            $this->sock = null;
            parent::close();
        }
        //Log::writelog("*************mysql connect closed......");
    }

    public function cache_error_sql($sql) {
        //缓存出错的sql
        if (!file_exists($this->config["errorsqlfile"])) {
            File::creat_dir_with_filepath($this->config["errorsqlfile"]);
        }
        $tmpsql = str_replace("\n", "", $sql);
        $writelogresult = File::write_file($this->config["errorsqlfile"], ($tmpsql . "\n"), "a");
        Log::writelog("mysql error($this->errno):{$this->error}");
        if ($r === TRUE && $this->errno != 1213) {
            Log::writelog("SQL Error" . ":" . $this->errorMessage(substr($sql, 0, 300)));
        }
    }

}

class MySQLiRecord implements IDbRecord {

    /**
     * @var \mysqli_result
     */
    public $result;

    function __construct($result) {
        $this->result = $result;
    }

    function __destruct() {
        if (is_object($this->result)) {
            $this->free();
            unset($this->result);
        }
    }

    function fetch_row() {
        if (!is_object($this->result)) {
            return false;
        }
        $return = $this->result->fetch_row();
        if ($return) {
            return $return;
        } else {
            $this->free();
            return false;
        }
    }

    function fetch() {
        if ($this->result === TRUE) {
            return TRUE;
        }
        if (!is_object($this->result)) {
            return false;
        }
        $return = $this->result->fetch_assoc();
        $this->free();
        return $return;
    }

    function fetchall() {
        if ($this->result === TRUE) {
            return TRUE;
        }
        if (!is_object($this->result)) {
            return false;
        }
        $data = array();
        $data = $this->result->fetch_all(MYSQLI_ASSOC);
        $this->free();
        return $data;
    }

    function __call($name, $arguments) {
        if ($this->result === TRUE) {
            return TRUE;
        }
        if (!is_object($this->result)) {
            return false;
        }
        @$r = call_user_func_array(array($this->result, $name), $arguments);
        if ($r) {
            return $r;
        } else {
            $this->free();
            return $r;
        }
    }

    function free() {
        $this->result->free_result();
        unset($this->result);
    }

    function count() {
        return inval($this->result->num_rows);
    }

}
