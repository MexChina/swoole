<?php

namespace Swoole\Core\Lib;

use \swoole_client;
use Swoole\Core\Log;
use Swoole\Core\App;

class Swoolclient {

    private $client;
    private $host;
    private $port;
    private $timeout = 0.2;
    private $receive_data = ""; //接收到的数据
    public static $clientcount = 0;  //客户端数量
    private $connect_back_function;
    private $receive_back_function;
    private $conect_type;

    function __construct($host = "127.0.0.1", $port = "10000", $conect_type = SWOOLE_SOCK_ASYNC, $timeout = 0.2) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->conect_type = $conect_type;
    }

    function onConnect(swoole_client $cli) {
        //echo "客户端已经连接......<br/>";
        if (is_object($this->connect_back_function) || function_exists($this->connect_back_function)) {
            call_user_func($this->connect_back_function);
        }
        Log::writelog("client connect $host success.......");
    }

    function onReceive(swoole_client $cli, $data) {
        if ($this->checkclose($data)) {
            return;
        }
        $this->receive_data .= $data;
        $datas = $this->dealInput($this->receive_data);
        foreach ($datas as $data) {
            if (is_object($this->receive_back_function) || function_exists($this->receive_back_function)) {
                call_user_func_array($this->receive_back_function, ["params" => $data]);
            }
        }
    }

    function receive() {
        $data = $this->client->recv();
        if ($this->checkclose($data)) {
            return;
        }
        $this->receive_data .= $data;
        $datas = $this->dealInput($this->receive_data);
        return $datas[0];
    }

    function onError(swoole_client $cli) {
        Log::writelog("client error code:{$cli->errCode} .......");
    }

    function onClose(swoole_client $cli) {
        
        Log::writelog("client connect $host close .......");
    }

    function close() {
        $this->client->send("CLOSEEND\r\n");
        unset($this->client);
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

    //发包
    public function send($package, $function = "") {
        $this->receive_back_function = $function;
        if (is_array($package)) {
            $package = $this->package($package);
        }
        $result = false;
        if ($package) {
            if (strlen($package) >= 2 * 1024 * 1024) {
                $tmp_file = tempnam('/dev/shm/swoole', 'sendmsg_');
                file_put_contents($tmp_file, $package);
                $result = $this->client->sendfile($tmp_file);
                if ($result) {
                    \Swoole\Core\AppServer::$instances->swoole->after(3000, function() use ($tmp_file) {
                        unlink($tmp_file);
                    });
                }
            } else {
                $result = $this->client->send($package);
            }
        } elseif (file_exists($file)) {
            $result = $this->client->sendfile($file);
        }
        return $result;
    }

    function connect($connect_back_function = "") {
        //echo "开始创建连接......<br/>";
        $this->connect_back_function = $connect_back_function;
        $testclient = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        if ($testclient->connect($this->host, $this->port, $this->timeout) === false) {
            return false;
        }
        if ($this->conect_type === SWOOLE_SOCK_ASYNC) {
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

}
