<?php

namespace Swoole\Core;

use Swoole\Core\Lib\Cache;
use Swoole\Core\Config;
use Swoole\Core\Log;
use Swoole\Core\Helper\File;
use \mysqli;
use \swoole_server;

class MysqlProxy {

    protected $pool_size = 10;      //连接池大小
    protected $idle_pool = array(); //空闲连接
    protected $busy_pool = array(); //工作连接
    protected $wait_queue = array(); //等待的请求
    protected $dbsock_port = array(); //dbsock对应port map列表
    protected $wait_queue_max = 200; //等待队列的最大长度，超过后将拒绝新的请求
    protected $config;
    protected $host = "127.0.0.1"; //server 运行的host
    protected $worker_num = 1; //开启处理进程数
    protected $dbnames = array(); //数据库监听端口列表
    protected $dbs = array(); //数据库列表
    protected $receive_data = array(); //接受到的客户端的数据包
    protected $client_querys = array(); //管理每个客户端执行当前有多少正在排队或者执行的查询数
    protected $client_status = array(); //管理客户端当前状态 
    protected $cache;

    /**
     * @var swoole_server
     */
    protected $serv;

    function run() {
        $config = Config::instance();
        $this->config = $config->get("dbproxy");
        Log::setLogfile($this->config['server']['logfileDir'], $this->config['server']['logfileName']);
        $dbproxys = $this->dbs = $this->config['db'];
        $this->dbnames = array_keys($dbproxys);
        $serv = $this->serv = new swoole_server($this->host, $this->config['server']['port']);
        //创建共享文件目录
        if (is_dir($this->config['server']['share_dir'])) {
            File::creat_dir_with_filepath($this->config['server']['share_dir']);
        }

        $swoole_config = $this->config['swoole'];
        $swoole_config['worker_num'] = count($this->dbnames) + 1;
        $serv->set($swoole_config);
        $serv->on('Start', array($this, 'onStart'));
        $serv->on('WorkerStart', array($this, 'onWorkerStart'));
        $serv->on('Connect', array($this, 'onConnect'));
        $serv->on('PipeMessage', array($this, 'onPipeMessage'));
        $serv->on('Receive', array($this, 'onReceive'));
        $serv->on('close', array($this, 'onClose'));
        $serv->on('Shutdown', array($this, 'onShutdown'));
        $serv->on('WorkerError', array($this, 'onWorkerError'));

        register_shutdown_function(array($this, 'handleFatal')); //注册错误处理函数
        $serv->start();
    }

    function onWorkerStart($serv, $worker_id) {
        $this->workerid = $worker_id;
        if ($worker_id >= $serv->setting['worker_num']) {
            swoole_set_process_name("mysqlProxyTaskWorker$worker_id");
            Log::writelog("task worker(id:$worker_id) start ");
        } else {
            swoole_set_process_name("mysqlProxyEventWorker$worker_id");
            Log::writelog("event worker(id:$worker_id) start ");
        }

        $this->serv = $serv;
        $dbname = $this->dbnames[$this->serv->worker_id];
        $dbconfig = $this->dbs[$dbname];
        $this->pool_size = isset($dbconfig['pool_size']) ? $dbconfig['pool_size'] : $this->pool_size;
        for ($i = 0; $i < $this->pool_size; $i++) {
            $this->connect_mysql();
        }
    }

    function onStart($serv) {
        if ($matser_pid = $serv->master_pid) {
            file_put_contents(MASTER_PID_FILE, $matser_pid);
        }
        swoole_set_process_name("mysqlProxyMasterProcess");
        Log::writelog("mysqlProxy master process start......");
    }

    function onConnect($serv, $fd, $from_id) {
        Log::writelog("client  connect......");
        $fdinfo = $serv->connection_info($fd);
        Log::writelog("{$fdinfo['remote_ip']}:{$fdinfo['remote_port']} connect......");
        $this->client_status[$fd] = 1;
    }

    function onClose($serv, $fd, $from_id) {
        Log::writelog("client[$fd]  closed......");
        if ($this->client_querys[$fd] == 0) {
            unset($this->client_status[$fd]);
            unset($this->client_querys[$fd]);
        }
        unset($this->receive_data[$fd]);
    }

    function onWorkerError(swoole_server $serv, int $worker_id, int $worker_pid, int $exit_code) {
        Log::writelog("work[$worker_id] error: code [$exit_code]");
    }

    function onShutdown(swoole_server $server) {
        Log::writelog("server is shutdown...... ");
    }

    function onSQLReady($db_sock) {
        $db_res = $this->busy_pool[$db_sock];
        //如果超时就重连
        if (empty($db_res)) {
            swoole_event_del($db_sock);
            $db_res = $this->idle_pool[$db_sock];
            unset($this->idle_pool[$db_sock]);
            $mysqli = $db_res['mysqli'];
            $this->connect_mysql($mysqli);
            return;
        }
        $mysqli = $db_res['mysqli'];
        $fd = $db_res['fd'];
        $sqlid = $db_res['sqlid'];
        $worker_id = $db_res['worker_id'];
        Log::writelog(__METHOD__ . ": dbname={$this->dbnames[$this->serv->worker_id]}|client_sock=$fd|db_sock=$db_sock|dbport={$this->dbnames[$this->serv->worker_id]}|sqlid=$sqlid");
        if (!is_object($mysqli)) {
            $result['code'] = -2;
            $result['result'] = sprintf("MySQLi Error: Link disconnect");
        } else {
            if ($mresult = $mysqli->reap_async_query()) {
                //echo "var type:";var_dump(gettype($result));
                $result['code'] = 0;
                if (is_object($mresult)) {
                    $ret = $mresult->fetch_all(MYSQLI_ASSOC);
                    $result['result'] = $ret;
                    mysqli_free_result($mresult);
                } else {
                    $result['result'] = $mresult;
                }
            } else {
                $result['code'] = -2;
                $result['result'] = sprintf("MySQLi Error: %s", substr(mysqli_error($mysqli), 0, 100));
            }
        }
        $this->restowork($fd, $result, $sqlid, $worker_id);
        //release mysqli object
        $this->idle_pool[$db_sock] = $db_res;
        unset($this->busy_pool[$db_sock]);
        //这里可以取出一个等待请求
        if (count($this->wait_queue) > 0) {
            $idle_n = count($this->idle_pool);
            for ($i = 0; $i < $idle_n; $i++) {
                $req = array_shift($this->wait_queue);
                $this->doQuery($req['fd'], $req['sql'], $req['sqlid'], $req['worker_id']);
            }
        }
    }

    function onPipeMessage($server, $from_worker_id, $message) {
        $message = unserialize($message);
        $sqlid = $message['sqlid'];
        $fd = $message['fd'];
        if ($message['sql']) {
            $data = gzuncompress($message['sql']);
            //echo "Received from [$fd:$sqlid]: $data\n";
            //没有空闲的数据库连接
            if (count($this->idle_pool) == 0) {
                //等待队列未满
                if (count($this->wait_queue) < $this->wait_queue_max) {
                    $this->wait_queue[] = array(
                        'fd' => $fd,
                        'sql' => $data,
                        'sqlid' => $sqlid,
                        'worker_id' => $from_worker_id
                    );
                } else {
                    $result['code'] = -1;
                    $result['result'] = "request too many, Please try again later.";
                    $this->restowork($fd, $result, $sqlid, $from_worker_id);
                }
            } else {
                $this->doQuery($fd, $data, $sqlid, $from_worker_id);
            }
        } elseif ($message['result'] || $message['result_file']) {
            $this->client_querys[$fd] --;
            //如果客户端任然处于连接状态则返回数据，否则清理
            if ($this->serv->connection_info($fd)) {
                if ($message['result']) {
                    $result = $message['result'];
                    Log::writelog("(dbname:{$this->dbnames[$this->serv->worker_id]}, sqlid:$sqlid)send back length:" . (strlen($result) + 8) . " ......");
                    $this->send($fd, $result);
                } else {
                    $result_file = $message['result_file'];
                    Log::writelog("(dbname:{$this->dbnames[$this->serv->worker_id]}, sqlid:$sqlid)send back length:" . filesize($result_file) . " ......");
                    $this->send($fd, "", $result_file);
                }
            } else {
                unset($this->client_querys[$fd]);
                unset($this->receive_data[$fd]);
                unset($this->client_status[$fd]);
            }
            //客户端请求关闭以后完成查询即可关闭链接
            if ($this->client_status[$fd] == 0 && $this->client_querys[$fd] == 0) {
                $this->send($fd, "CLOSEEND\r\n");
                $this->serv->close($fd);
            }
        }
    }

    function onReceive($serv, $fd, $from_id, $data) {
        if ($this->checkclose($data, $fd)) {
            return;
        }
        $this->receive_data[$fd] = $this->receive_data[$fd] . $data;
        $datas = $this->dealInput($this->receive_data[$fd]);

        foreach ($datas as $sqlid => $mdata) {
            $sqlid = intval(substr($sqlid, 4));
            if ($this->checkclose($mdata, $fd)) {
                return;
            }
            if (preg_match("/\|\-(.+?)\-\|([\w\W]+)/", $mdata, $match)) {
                $dbname = trim($match[1]);
                $sql = $match[2];
                //Log::writelog("sqlid=$sqlid | dbname=$dbname......"); 
                switch ($sqlid) {
                    case 0:
                        $config_response = $this->dbs[$dbname][$sql]; //客户端获取相关配置选项
                        $this->send($fd, $this->package($config_response, $sqlid));
                        break;
                    default:
                        $work_id = intval(array_search($dbname, $this->dbnames));
                        $message = array();
                        $message['sqlid'] = $sqlid;
                        $message['sql'] = $sql;
                        $message['fd'] = $fd;
                        $message = serialize($message);
                        //Log::writelog("worker_id:{$work_id} dbname:{$dbname}  this worker_id:{$serv->worker_id}");
                        if ($work_id == $serv->worker_id) {
                            $this->onPipeMessage($serv, $work_id, $message);
                        } else {
                            $this->serv->sendMessage($message, $work_id);
                        }
                        $this->client_querys[$fd] ++;
                        break;
                }
            }
        }
    }

    function doQuery($fd, $sql, $sqlid, $from_worker_id) {
        //从空闲池中移除
        //$db = array_pop($this->idle_pool);
        $db = $this->get_mysql_sock();
        $mysqli = $db['mysqli'];
        Log::writelog("[db_sock:{$db['db_sock']}]  start do query(sqlid:{$sqlid})......");
        for ($i = 0; $i < 2; $i++) {
            $result = $mysqli->query($sql, MYSQLI_ASYNC);
            if ($result === false) {
                if ($mysqli->errno == 2013 or $mysqli->errno == 2006) {
                    $mysqli->close();
                    $r = $mysqli->connect();
                    Log::writelog("[db_sock:{$db['db_sock']}] reconnect {$i} times ......");
                    if ($r === true) {
                        continue;
                    }
                    Log::writelog("[db_sock:{$db['db_sock']}]  reconnect {$i} times failed ......");
                }
            }
            break;
        }
        $db['fd'] = $fd;
        $db['sqlid'] = $sqlid;
        $db['worker_id'] = $from_worker_id;
        //加入工作池中
        $this->busy_pool[$db['db_sock']] = $db;
    }

    //获取连接池中一个连接用于处理sql
    protected function get_mysql_sock() {
        $db_sock = array_rand($this->idle_pool);
        $db = $this->idle_pool[$db_sock];
        unset($this->idle_pool[$db_sock]);
        return $db;
    }

    //连接mysql
    protected function connect_mysql($db = "") {
        $worker_id = $this->serv->worker_id;
        $dbname = $this->dbnames[$worker_id];
        if ($dbname) {
            //Log::writelog("worker_id:{$worker_id}|dbname:{$dbname}");
            $dbconfig = $this->dbs[$dbname];
            $db = $db ? $db : new mysqli;
            $cresult = $db->connect("p:" . $dbconfig['host'], $dbconfig['user'], $dbconfig['passwd'], $dbconfig['name'], $dbconfig['port']);
            if ($cresult !== FALSE) {
                $db_sock = swoole_get_mysqli_sock($db);
                swoole_event_add($db_sock, array($this, 'onSQLReady'));
                $this->idle_pool[$db_sock] = array(
                    'mysqli' => $db,
                    'db_sock' => $db_sock,
                    'fd' => 0,
                    'sqlid' => 0,
                );
            } else {
                Log::writelog("{$dbconfig['host']} connect failed......");
            }
            Log::writelog("db({$dbname})[worker_id:{$worker_id}][db_sock:{$db_sock}] create pool success......");
        }
    }

    protected function package($resouce, $sqlid = 0) {
        $total_len = pack('N2', $sqlid, strlen($resouce) + 8);
        $req_package = $total_len . $resouce;
        return $req_package;
    }

    protected function restowork($fd, $result, $sqlid, $work_id) {
        $message['fd'] = $fd;
        $message['sqlid'] = $sqlid;
        $message['result'] = $this->package(gzcompress(serialize($result), 9), $sqlid);
        if (strlen($message['result']) > 10240) {//返回数据超过10K则以文件的形式返回
            $tmp_file = tempnam('/dev/shm/swoole', 'promsg_');
            file_put_contents($tmp_file, $message['result']);
            $message['result_file'] = $tmp_file;
            unset($message['result']);
        }
        $message = serialize($message);
        if ($this->serv->worker_id == $work_id) {
            $this->onPipeMessage($server, $from_worker_id, $message);
        } else {
            $this->serv->sendMessage($message, $work_id);
        }
    }

    //处理接收到的数据包
    protected function dealInput(& $recv_buffer) {
//      echo "$recv_buffer\n";
        $retrun = $retrun_next = array();
        // 接收到的数据长度
        $recv_len = strlen($recv_buffer);
        // 如果接收的长度还不够四字节，那么要等够四字节才能解包到请求长度
        if ($recv_len < 8) {
            // 不够四字节，等够四字节
            return false;
        }
//        echo "recv_len:$recv_len\n";
        // 从请求数据头部解包出整体数据长度
        $unpackbuffer = substr($recv_buffer, 0, 8);
        try {
            $unpack_data = unpack('N2int', $unpackbuffer);
        } catch (Exception $exc) {
//            var_dump($exc->getTraceAsString());
            $recv_buffer = "";
            return false;
        }
//        print_r($unpack_data);
        if (empty($unpack_data)) {
            return false;
        } else {
            $sqlid = $unpack_data['int1'];
            $total_len = $unpack_data['int2'];
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
        $retrun["sql_" . $sqlid] = $this->depackage($pack);
        if (!empty($retrun_next)) {
            $retrun = array_merge($retrun, $retrun_next);
        }
        return $retrun;
    }

    //解包
    protected function depackage($package) {
        $package = substr($package, 8);
//        $response = preg_match("/\|\-(.+?)\-\|([\w\W]+)/", $package, $match);
//        $response = gzuncompress($match[2]);
//        echo "package:$response\n";
        return $package;
    }

    protected function checkclose($data, $fd) {
        if ($data == "CLOSEEND\r\n") {
            if ($this->client_querys[$fd] == 0) {
                $this->send($fd, "CLOSEEND\r\n");
                $this->serv->close($fd);
                return TRUE;
            } else {
                $this->client_status[$fd] = 0;
            }
        }
        return false;
    }

    private function send($fd, $package, $file = "") {
        if ($package) {
            if (strlen($package) >= 2 * 1024 * 1024) {
                $tmp_file = tempnam('/dev/shm/swoole', 'sendmsg_');
                file_put_contents($tmp_file, $package);
                $result = $this->serv->sendfile($fd, $tmp_file);
                if ($result) {
                    $this->serv->after(3000, function() use ($tmp_file) {
                        unlink($tmp_file);
                    });
                }
            } else {
                $result = $this->serv->send($fd, $package);
            }
        } elseif (file_exists($file)) {
            $result = $this->serv->sendfile($fd, $file);
            if ($result) {
                $this->serv->after(3000, function() use ($file) {
                    unlink($file);
                });
            }
        } else {
            $result['code'] = -3;
            $result['result'] = "tmp file losed......";
            $result = $this->serv->send($fd, $this->package(gzcompress(serialize($result))));
        }
        return $result;
    }

    /*
     * 错误捕捉
     */

    protected function handleFatal() {
        $error = error_get_last();
        if (isset($error['type'])) {
            switch ($error['type']) {
                case E_ERROR :
                case E_PARSE :
                case E_DEPRECATED:
                case E_CORE_ERROR :
                case E_COMPILE_ERROR :
                    $message = $error['message'];
                    $file = $error['file'];
                    $line = $error['line'];
                    $log = "$message ($file:$line)\nStack trace:\n";
                    $trace = debug_backtrace();
                    foreach ($trace as $i => $t) {
                        if (!isset($t['file'])) {
                            $t['file'] = 'unknown';
                        }
                        if (!isset($t['line'])) {
                            $t['line'] = 0;
                        }
                        if (!isset($t['function'])) {
                            $t['function'] = 'unknown';
                        }
                        $log .= "#$i {$t['file']}({$t['line']}): ";
                        if (isset($t['object']) && is_object($t['object'])) {
                            $log .= get_class($t['object']) . '->';
                        }
                        $log .= "{$t['function']}()\n";
                    }
                    if (isset($_SERVER['REQUEST_URI'])) {
                        $log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
                    }
                    Log::writelog("system error: $log");
            }
        }
    }

}
