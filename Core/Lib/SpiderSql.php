<?php

namespace Swoole\Core\Lib;

use \swoole_client;
use Swoole\Core\Log;
use Swoole\Core\Lib\Database;

class SpiderSql {

    private $client;
    private $host;
    private $port;
    private $timeout = 0.2;
    private $receive_data = ""; //接收到的数据
    private $connect_back_function;
    private $query_back_function;
    private $result;
    private $db;
    private $save_table;
    private $save_result = MYSQLI_STORE_RESULT;
    private $conect_type;
    private $error;

    function __construct($host = "127.0.0.1", $port = "10100", $conect_type = SWOOLE_SOCK_ASYNC, $timeout = 0.2) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->conect_type = $conect_type;
    }

    function onConnect(swoole_client $cli) {
        //echo "客户端已经连接......<br/>";
        if (is_object($this->connect_back_function) || function_exists($this->connect_back_function)) {
            call_user_func($this->connect_back_function);
            unset($this->connect_back_function);
        }
        //Log::writelog("client connect $host success.......");
    }

    function onReceive(swoole_client $cli, $data) {
        if ($this->checkclose($data)) {
            return;
        }
        $this->receive_data .= $data;
        $datas = $this->dealInput($this->receive_data);
        foreach ($datas as $data) {
            if (!empty($data["config"]) && !empty($data["table"])) {
                $this->db = new Database($data["config"]);
                $this->save_table = $data["table"];
            } elseif (!empty($data["field_list"]) && !empty($data["db_data"])) {
                $this->result['field_list'] = $data["field_list"];
                $this->result['num_rows'] = $data["num_rows"];
                $this->result['db_data'] .= $data["db_data"];
            }
            if (!empty($data["result"])) {
                $this->get_mysql_result();
            }
            if (!empty($data["error"])) {
                $this->error = $data["error"];
                return;
            }
            unset($data);
        }
        if (!empty($datas) && !empty($this->result)) {
            if (is_object($this->query_back_function) || function_exists($this->query_back_function)) {
                call_user_func($this->query_back_function);
                unset($this->query_back_function);
            }
        }
        unset($datas);
    }

    function receive() {
        while (true) {
            $data = $this->client->recv();
            if (!empty($data)) {
                $error = $this->onReceive($this->client, $data);
                if (!empty($this->result)) {
                    return TRUE;
                }
                if (!empty($this->error)) {
                    return false;
                }
            }
        }
    }

    function error() {
        return $this->error;
    }

    function onError(swoole_client $cli) {
        //Log::writelog("client error code:{$cli->errCode} .......");
    }

    function onClose(swoole_client $cli) {
        //Log::writelog("client connect $host close .......");
    }

    function close() {
        //Log::writelog("client reponse close......");
        $this->client->send("CLOSEEND\r\n");
    }

    function fetch() {
        return $this->fetch_assoc();
    }

    private function get_mysql_result() {
        $result = $this->db->query("SELECT * FROM {$this->save_table}", ($this->save_result == MYSQLI_STORE_RESULT ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT));
        if (is_object($result->result)) {
            $this->result = $result;
        } else {
            $this->error = $this->db->error;
        }
    }

    function fetchall() {
        if (!$this->result) {
            $this->get_mysql_result();
        }
        if (is_object($this->result)) {
            $return = $this->result->fetch_all(MYSQLI_ASSOC);
            $this->free_result();
            return $return;
        } elseif (!empty($this->result["field_list"]) && !empty($this->result["db_data"])) {
            $return = [];
            foreach (explode("\r\n", $this->result["db_data"]) as $data) {
                $data = explode("\t", $data);
                $return[] = array_combine($this->result["field_list"], $data);
            }
            $this->result = "";
            return $return;
        } else {
            return false;
        }
    }

    function fetch_assoc() {
        if (!$this->result) {
            $this->get_mysql_result();
        }
        if (is_object($this->result)) {
            $row = $this->result->fetch_assoc();
            if (!$row) {
                $this->free_result();
            }
            return $row;
        } elseif (!empty($this->result["field_list"]) && !empty($this->result["db_data"])) {
            $this->result["db_data"] = is_array($this->result["db_data"]) ? $this->result["db_data"] : explode("\n", $this->result["db_data"]);
            if (empty($this->result["db_data"])) {
                $this->result = "";
                return false;
            } else {
                return array_combine($this->result["field_list"], explode("\t", array_shift($this->result["db_data"])));
            }
        } else {
            return false;
        }
    }

    function num_rows() {
        if ($this->save_result === MYSQLI_STORE_RESULT) {
            return $this->result->result->num_rows;
        } else {
            $this->result->free();
            return $this->db->query("SELECT COUNT(*) AS `num_rows` FROM $this->save_table")->fetch()['num_rows'];
        }
    }

    function free_result() {
        if (!empty($this->result)) {
            $this->result->free();
            $this->result = "";
            $this->error = "";
            $this->db->close();
            unset($this->db);
        }
    }

    protected function checkclose($data) {
        if ($data == "CLOSEEND\r\n") {
            $this->client->close();
            unset($this->client);
            return TRUE;
        }
        return false;
    }

    protected function package($response) {
        $response = is_array($response) ? serialize($response) : $response;
        $is_compress = 0;
        if (strlen($response) > 1024) {
            $is_compress = 1;
            $response = gzcompress($response, 9);
        }
        $total_len = pack('NC', strlen($response) + 4 + 1, $is_compress);
        $req_package = $total_len . $response;
        return $req_package;
    }

    //处理接收到的数据包
    protected function dealInput(& $recv_buffer) {
//      echo "$recv_buffer\n";
        $retrun = $retrun_next = array();
        // 接收到的数据长度
        $recv_len = strlen($recv_buffer);
        // 如果接收的长度还不够四字节，那么要等够四字节才能解包到请求长度
        if ($recv_len < 5) {
            // 不够四字节，等够四字节
            return false;
        }
//        echo "recv_len:$recv_len\n";
        // 从请求数据头部解包出整体数据长度
        $unpackbuffer = substr($recv_buffer, 0, 5);
        try {
            $unpack_data = unpack('Nint/Cis_compress', $unpackbuffer);
        } catch (Exception $exc) {
//            var_dump($exc->getTraceAsString());
            $recv_buffer = "";
            return false;
        }
//        print_r($unpack_data);
        if (empty($unpack_data)) {
            return false;
        } else {
            $total_len = $unpack_data['int'];
            $is_compress = $unpack_data['is_compress'];
            $recvlength = $total_len - $recv_len;
            if ($recvlength > 0) {
                return false;
            } elseif ($recvlength < 0) {
                $recvlength = abs($recvlength);
                $pack = substr($recv_buffer, 0, strlen($recv_buffer) - $recvlength);
                $recv_buffer = substr($recv_buffer, strlen($recv_buffer) - $recvlength, $recvlength);
                if ($recv_buffer) {
                    $retrun_next = $this->dealInput($recv_buffer);
                }
            } else {
                $pack = $recv_buffer;
                $recv_buffer = "";
            }
        }
        $retrun[] = $this->depackage($pack, $is_compress);
        if (!empty($retrun_next)) {
            $retrun = array_merge($retrun, $retrun_next);
        }
        return $retrun;
    }

    //解包
    protected function depackage($package, $is_compress = 0) {
        $package = substr($package, 5);
        if ($is_compress == 1) {
            $ungz_request = gzuncompress($package);
        } else {
            $ungz_request = $package;
        }
        $request = unserialize($ungz_request);
        if (!$request) {
            $request = $ungz_request;
        }
        //echo "package:$response\n";
        return $request;
    }

    //执行sql
    public function query($sql, $spider_server_name = "resume_source_data", $save_result = MYSQLI_STORE_RESULT, $cache_time = 0, $function = "") {
        $this->query_back_function = $function;
        $this->save_result = $save_result;
        if (!$sql) {
            return FALSE;
        }
        $msg = array(
            "params" => [
                "sql" => $sql,
                "cache_time" => $cache_time,
                "spider_server_name" => $spider_server_name,
            ],
        );
        $package = $this->package($msg);
        $result = false;
        if ($package) {
            $result = $this->client->send($package);
        }
        if ($this->conect_type === SWOOLE_SOCK_ASYNC) {
            return $result;
        } else {
            return $this->receive();
        }
    }

    function connect($connect_back_function = "") {
        //echo "开始创建连接......<br/>";
        $this->connect_back_function = $connect_back_function;
        $testclient = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        if ($testclient->connect($this->host, $this->port, $this->timeout) === false) {
            return false;
        }
        if ($this->conect_type === SWOOLE_SOCK_ASYNC) {
            $testclient->close();
            unset($testclient);
            $this->client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC); //异步非阻塞
            $this->client->on('connect', array($this, 'onConnect'));
            $this->client->on('receive', array($this, 'onReceive'));
            $this->client->on('error', array($this, 'onError'));
            $this->client->on('close', array($this, 'onClose'));
            $this->client->connect($this->host, $this->port, $this->timeout);
            self::$clientcount++;
        } else {
            $this->client = $testclient;
        }
        return TRUE;
    }

    function __destruct() {
        $this->close();
    }

}
