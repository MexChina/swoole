<?php

namespace Swoole\Core;

use Swoole\Core\Helper\File;
use Swoole\Core\Lib\Database;
use Swoole\Core\Lib\Cache;
use Swoole\Core\Lib\IDFactory;
use Swoole\Core\Config;
use Swoole\Core\Lib\Swoolclient;
use Swoole\Core\Log;
use Swoole\Core\App;
use \swoole_server;
use \swoole_http_server;
use \swoole_websocket_server;

class AppServer {

    public static $instances = null;
    public static $cache;  //缓存类实例
    public static $idFactory; //唯一ID生成对象
    public static $tables; //共享内存池
    public static $config; // 配置文件
    public $timerParams;  //计时器管理表
    public $swoole;  //swoole server对象
    public $receive_data = ""; //客户端传入数据
    public $clients; //管理客户端链接信息
    public $daemons; //管理逻辑处理对象
    public $currentFd; //当前连接fd
    public $processId;  //当前工作进程唯一标识
    public $workerid; //当前进程ID
    public $timers = array(); //当前进程的定时器
    public $lock;
    public $http_fd = [];
    private $worker_status = []; //监控进程记录服务器状态,存储各个工作进程应答心跳请求的记录
    private $worker_close_status = []; //请求关闭的工作进程

    function __construct() {
        global $_G;
        $_G["localhost"] = swoole_get_local_ip();
        self::$config = Config::instance();
        if (!is_dir(self::$config->get("server[logfileDir]"))) {
            echo "log file path:" . self::$config->get("server[logfileDir]") . "\n";
            File::creat_dir(self::$config->get("server[logfileDir]"));
        }
        Log::setLogfile(self::$config->get("server[logfileDir]"));
        //Log::setBasePath(self::$config->get("server[logfileDir]"));

//        self::$cache = new Cache();
        //$this->lock = new Lock();
        self::$idFactory = new IDFactory(self::$config->get("idfactory"));
        //创建共享文件目录
        if (is_dir(self::$config->get('server[share_dir]'))) {
            File::creat_dir_with_filepath(self::$config->get('server[share_dir]'));
        }
       // App::init("init_cache");
        $port = self::$config->get('server[port]');
        try {
            if ($_G['server_type'] == "http") {
                $this->swoole = new swoole_http_server("0.0.0.0", $port);
            } elseif ($_G['server_type'] == "websocket") {
                $this->swoole = new swoole_websocket_server("0.0.0.0", $port);
            } else {
                $this->swoole = new swoole_server("0.0.0.0", $port);
            }
        } catch (Exception $ex) {
            echo("swoole 服务器启动异常......" . $ex->getMessage());
        }

        $this->swooleSet(self::$config->get("swoole"));
        //加载版本文件
        //@include_once self::$config->get("server[versionFile]");
        //Log::writelog("VERSION：" . NCDEAMON_VERSION); //版本号管理
        register_shutdown_function(array($this, 'handleFatal')); //注册错误处理函数
    }

    public static function instance() {
        if (!self::$instances) {
            self::$instances = new self();
        }
        return self::$instances;
    }

    function swooleSet($array_config) {
        //设置动态worker进程，可以根据实际需要动态 调整worker进程的多少, 在APP 目录中设置
        $worker_num = intval(App::init("get_worker_number"));
        $array_config['worker_num'] = $worker_num ? $worker_num : $array_config['worker_num'];
        if (!empty(self::$config->get("server[worker_heart_time]"))) {
            $array_config['worker_num'] = $array_config['worker_num'] + 1; //增加监控检测进程
        }
        $this->swoole->set($array_config);
    }

    function onStart($serv) {
        if ($matser_pid = $this->swoole->master_pid) {
            file_put_contents(MASTER_PID_FILE, $matser_pid);
        }
        swoole_set_process_name("swoole" . SWOOLE_APP . "Master");
        Log::writelog("master process start ......");
    }

    function onManagerStart($serv) {
        swoole_set_process_name("swoole" . SWOOLE_APP . "Manager");
        Log::writelog("manager process start ......");
    }

    function onWorkerStart($serv, $worker_id) {
        $this->workerid = $worker_id;
        define("SWOOLE_WORKER_ID", $worker_id);
        if ($worker_id >= $serv->setting['worker_num']) {
            swoole_set_process_name("swoole" . SWOOLE_APP . "Tasker{$worker_id}");
            Log::writelog("task worker(id:$worker_id) start ......");
            //执行task进程初始化程序
            App::init("tasker_init");
        } else {
            swoole_set_process_name("swoole" . SWOOLE_APP . "Worker{$worker_id}");
            if ($worker_id == ($serv->setting['worker_num'] - 1) && !empty(self::$config->get("server[worker_heart_time]"))) {
                //初始化监控进程
                swoole_set_process_name("swoole" . SWOOLE_APP . "Monitor{$worker_id}");
                Log::writelog("monitor worker(id:$worker_id) start ......");
                define("MONITOR_WORK", TRUE);
                $hert_time = self::$config->get("server[worker_heart_time]");
                $r = swoole_timer_tick($hert_time * 1000, array($this, "send_check_worker_heart"));
                App::init("timer_init");
            } else {
                Log::writelog("event worker(id:$worker_id) start ......");
                //执行work进程初始化程序
                App::init("worker_init");
            }
        }
    }

    function onConnect($serv, $fd, $from_id) {
        $fdinfo = $serv->connection_info($fd);
        //$this->sendmsgdata("客户端连接成功！");
        Log::writelog("Client Connected to WorkerProcess(id:$this->workerid) ......", $fdinfo);
//        if (!in_array($fdinfo['remote_ip'], self::$config->get("server[allowClient]"))) {
//            $this->swoole->close($fd);
//        }
    }

    function onPipeMessage($server, $from_worker_id, $data) {
        $this->parsh_work_message($data);
        //心跳检测
        switch ($data) {
            case "~~mreq":
                $this->restowork("~~mresp", $from_worker_id); //应答心跳
                break;
            case "~~mresp":
                if (defined("MONITOR_WORK")) {
                    $this->recv_check_worker_heart($from_worker_id);
                }
                break;
            case "~~mresclose":
                if (defined("MONITOR_WORK")) {
                    $this->resquest_close_worker($from_worker_id);
                }
                break;
            default:
                //传递来源worker_id
                $data['params']['from_worker_id'] = $from_worker_id;
                App::doaction($data);
                break;
        }
    }

    function onReceive($serv, $fd, $from_id, $data) {
        /*
         * 访问参数及路由规则
         * type=App&appname=Count&action=index&params=[]
         */
        if ($this->checkclose($data, $fd)) {
            return;
        }
        $this->receive_data[$fd] = $this->receive_data[$fd] . $data;
        $datas = $this->dealInput($this->receive_data[$fd]);
        foreach ($datas as $data) {
            $data['params']['fd'] = $fd;
            $data['type'] = "receive";
            App::doaction($data);
        }
    }

    function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
        $request_log = "";
        $request->server['remote_addr'] = empty($request->header['x-real-ip']) ? $request->server['remote_addr'] : $request->header['x-real-ip'];
        $request->server['remote_port'] = empty($request->header['x-real-port']) ? $request->server['remote_port'] : $request->header['x-real-port'];
        try {
            array_walk($request->server, function($value, $key) use (&$request_log) {
                $request_log .= "$key:$value ";
            });
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        Log::writelog($request_log);
        unset($request_log);
        $this->http_fd[$response->fd] = 1;
        $time_out = intval(self::$config->get('server[http_time_out]'));
        //定时任务判断是否超时
        if ($time_out > 0) {
            $server = $request->server;
            swoole_timer_after($time_out * 1000, function() use($response, $server) {
                if (!empty($this->http_fd[$response->fd])) {
                    Log::write_warning("{$server['remote_addr']}:{$server['remote_port']}\t{$server['request_uri']}?{$server['query_string']}\trequest timeout");
                    //$response->status(502);
                    $result = $response->end();
                    unset($this->http_fd[$response->fd]);
                }
            });
        }
        $data = [];
        $params['request'] = & $request;
        $params['response'] = & $response;
        if (!empty($request->get['app'])) {
            $data['app'] = $request->get['app'];
        }
        if (!empty($request->get['action'])) {
            $data['action'] = $request->get['action'];
        }
        $data['params'] = $params;
        $response_data = App::doaction($data);
        if (!empty($response_data)) {
            $response->end($response_data);
        }
    }

    function onClose($serv, $fd, $from_id) {
        $fdinfo = $serv->connection_info($fd);
        Log::writelog("Close Client Connect", $fdinfo);
        $data["action"] = "client_close";
        $data['params']['fd'] = $fd;
        $data['type'] = "close";
        App::doaction($data);
    }

    function onTimer($serv, $time) {
        Log::writelog("WorkerProcessid:$this->workerid timer start running ......");
    }

    function onTask($serv, $task_id, $from_id, $data) {
        //Log::writelog("Task[tasker_id：" . $serv->worker_id . "] start......");
        $tmp_data['params'] = unserialize($data);
        //$response_data["request"] = $tmp_data;
        $response_data["task_id"] = $task_id; //返回给zhu进程当前处理的taskid
        unset($data);
        //$response_data["action"] = !empty($tmp_data["back_action"]) ? $tmp_data["back_action"] : ""; //回调方法名
        $tmp_data['type'] = $response_data["worker_type"] = "task";
        $tmp_data['params']['from_worker_id'] = $from_id;
        $result = App::doaction($tmp_data);
        if ($result) {
            $response_data["params"] = $result;
            $this->restowork($response_data, $from_id);
        }
    }

    function onFinish($serv, $task_id, $data) {
        Log::writelog("Task$task_id: finished.");
    }

    function onWorkerError() {
        Log::write_error("WorkerProcessid:{$this->workerid} last error:" . var_export(error_get_last(), true));
        App::init("worker_stop");
        Log::write_error("WorkerProcessid:{$this->workerid} error  ......");
        //Log::flushBuffer();
    }

    function onManagerStop() {
        Log::writelog("Manager:{$this->workerid} stop  ......");
        //Log::flushBuffer();
    }

    function onWorkerStop() {
        App::init("worker_stop");
        Log::writelog("WorkerProcessid:{$this->workerid} stop ......");
        //Log::flushBuffer();
    }

    function onShutdown() {
        App::init("server_close");
        Log::writelog("swoole  Shutdown");
        if (file_exists(MASTER_PID_FILE)) {
            unlink(MASTER_PID_FILE);
        }
        //Log::flushBuffer();
    }

    function run() {
        global $_G;
        $this->swoole->on('start', array($this, 'onStart'));
        if ($_G['server_type'] == 'http') {
            $this->swoole->on('request', function ($request,$response){
                $request_rui = $request->server['path_info'];
                if($request->get['debug']){
                    $user_agent = $request->header['user-agent'];
                    $client_ip = $request->server['remote_addr'];
                    echo date("Y-m-d H:i:s"),"\t",$client_ip,"\t",$user_agent,"\n";
                }
                if($request_rui !== '/favicon.ico'){

                    if(strpos($request_rui,'.css') !== false || strpos($request_rui,'.js') !== false || strpos($request_rui,'.gif') !== false || strpos($request_rui,'.png') !== false || strpos($request_rui,'.jpg') !== false){
                        $file_url = SWOOLE_APP_DIR."static".$request_rui;
                        if(file_exists($file_url)){
                            $response->status(200);
                            if(strpos($request_rui,'img') !== false || strpos($request_rui,'images') !== false){
                                $response->header('Content-Type','image/jpeg');
                            }
                            $response->sendfile($file_url);die;
                        }else{
                            $response->status(404);
                            $response->end('Not found');die;
                        }
                    }

                    $route = self::$config->get("route[$request_rui]");

                    if(empty($route)){
                        $response->status(404);
                        $response->end('Not found');die;
                    }else{
                        $response->header('Content-Type','text/html; charset=UTF-8');
                        $response->status(200);
                    }
                    list($controller,$action) = $route;
                    $app_name = "Swoole\\App\\" . SWOOLE_APP . "\\".$controller;
                    $app = new $app_name();
                    $data = $app->$action($request,$response);
                    $response->end($data);
                }
            });
        }
        $this->swoole->on('managerStart', array($this, 'onManagerStart'));
        $this->swoole->on('workerStart', array($this, 'onWorkerStart'));
        $this->swoole->on('connect', array($this, 'onConnect'));
        $this->swoole->on('pipeMessage', array($this, 'onPipeMessage'));
        $this->swoole->on('receive', array($this, 'onReceive'));
        $this->swoole->on('task', array($this, 'onTask'));
        $this->swoole->on('finish', array($this, 'onFinish'));
        $this->swoole->on('timer', array($this, 'onTimer'));
        $this->swoole->on('close', array($this, 'onClose'));
        $this->swoole->on('workerStop', array($this, 'onWorkerStop'));
        $this->swoole->on('shutdown', array($this, 'onShutdown'));
        $this->swoole->on('managerStop', array($this, 'onManagerStop'));
        $this->swoole->on('workerError', array($this, 'onWorkerError'));
        $this->swoole->start();
    }

//关闭连接
    public function closeclient($fd) {
        $this->swoole->close($fd);
    }

//发送数据到客户端
    public function response($data, $fd, $recordlog = true) {
        $fdinfo = $this->swoole->connection_info($fd);
        if ($recordlog) {
            Log::writelog("response to client[code={$data['code']}]:{$data['result']}", $fdinfo);
        }
        return $this->sendmsg($fd, $this->package($data));
    }

//发送数据到另外一个进程
    protected function restowork($result, $work_id) {
        $message['data_type'] = 0;
        $result = serialize($result);
        if (strlen($result) > 10240) {//数据包大于10K则需要压缩
            $result = bin2hex(gzcompress($result, 9));
            $message['data_type'] = 1;
        }
        $message['result'] = $result;
        if (strlen($result) > 2097152) {//数据包大于2M则以文件的形式传递
            $tmp_file = tempnam('/dev/shm/swoole', 'promsg_');
            file_put_contents($tmp_file, $message['result']);
            $message['result'] = $tmp_file;
            $message['data_type'] = 2;
        }
        $message = serialize($message);
        if ($this->swoole->worker_id == $work_id) {
            $result = $this->onPipeMessage($server, $this->swoole->worker_id, $message);
        } else {
            $result = $this->swoole->sendMessage($message, $work_id);
        }
        return $result;
    }

    public function reponse_to_web(\swoole_http_response $response, $data = "") {
        $result = false;
        if (!empty($this->http_fd[$response->fd])) {
            unset($this->http_fd[$response->fd]);
            if (!empty($data)) {
                $result = $response->end($data);
            } else {
                $response->status(200);
                $result = $response->end();
            }
        }
        return $result;
    }

//向其他进程发送数据
    public function send_to_worker($data, $work_id) {
        return $this->restowork($data, $work_id);
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

    protected function checkclose($data, $fd) {
        if ($data == "CLOSEEND\r\n") {
            $this->swoole->send($fd, "CLOSEEND\r\n", $this->workerid);
            $this->swoole->close($fd);
            $this->receive_data[$fd] = "";
            unset($this->receive_data[$fd]);
            return TRUE;
        }
        return false;
    }

    private function recv_check_worker_heart($work_id) {
        $response_time = round((microtime(TRUE) - $this->worker_status[$work_id]['time']) * 1000, 2);
        $this->worker_status[$work_id]['fail_num'] = 0;
        Log::write_notice("worker_{$work_id} response use {$response_time}ms");
    }

    public function send_check_worker_heart() {
        $work_num = $this->swoole->setting['worker_num'] + $this->swoole->setting['task_worker_num'];
        for ($i = 0; $i < $work_num; $i++) {
            if ($i != $this->workerid) {
                @$fail_num = empty($this->worker_status[$i]['fail_num']) ? 0 : $this->worker_status[$i]['fail_num'];
                if ($fail_num >= 3) {//三次没有应答则判断为已经僵死，重启动该进程
                    Log::write_log("will kill worker_{$i}, fail_num:{$fail_num}......");
                    unset($this->worker_status[$i]);
                    $work_type = ($i < $this->swoole->setting['worker_num'] - 1) ? "Worker" : "Tasker";
                    $fail_num = 0;
                    $ret = shell_exec("ps aux | grep 'swoole" . SWOOLE_APP . "{$work_type}{$i}' | grep -v grep");
                    if (preg_match("/^[\S]+\s+(\d+)\s+.+(swoole.+)$/", $ret, $match)) {
                        $tmp_pid = $match[1];
                        $process_name = $match[2];
                        if ($tmp_pid) {
                            posix_kill($tmp_pid, SIGKILL);
                            Log::write_notice("$process_name is no reply, kill $process_name ......");
                            sleep(1);
                        }
                    }
                }
                $r = $this->restowork("~~mreq", $i);
                if ($r) {
                    $this->worker_status[$i]['time'] = microtime(TRUE);
                    $this->worker_status[$i]['fail_num'] = $fail_num + 1;
                    Log::write_notice("send heart check sign to worker_{$i}, fail_num:{$fail_num}......");
                }
            }
        }
    }

//向监控进程发送关闭该worker进程的请求
    public function close_worker() {
        $monitor_worker_id = $this->swoole->setting['worker_num'] - 1;
        $this->restowork("~~mresclose", $monitor_worker_id);
    }

    //处理关闭进程请求，如果所有的进程都请求关闭，则立刻关闭服务
    private function resquest_close_worker($worker_id) {
        $this->worker_close_status[] = $worker_id;
        if (count($this->worker_close_status) == $this->swoole->setting['worker_num']-1) {
            $this->swoole->shutdown();
        }
    }

//向客户端发送数据
    public function sendmsg($fd, $package, $file = "") {
        if ($fd === NULL || $fd === FALSE || !is_numeric($fd)) {
            return;
        }
        $package = $this->package($package);
        $result = false;
        if ($package) {
            if (strlen($package) >= 2 * 1024 * 1024) {
                $tmp_file = tempnam('/dev/shm/swoole', 'sendmsg_');
                file_put_contents($tmp_file, $package);
                $result = $this->swoole->sendfile($fd, $tmp_file);
                if ($result) {
                    $this->swoole->after(3000, function() use ($tmp_file) {
                        unlink($tmp_file);
                    });
                }
            } else {
                $result = $this->swoole->send($fd, $package, $this->workerid);
            }
        } elseif (file_exists($file)) {
            $result = $this->swoole->sendfile($fd, $file);
        }
        return $result;
    }

//解析从其他进程发送过来的消息
    protected function parsh_work_message(& $data) {
        $data = unserialize($data);
        switch ($data['data_type']) {
            case 1://压缩
                $data = unserialize(gzuncompress(hex2bin($data["result"])));
                break;
            case 2://文件
                $data = unserialize(gzuncompress(hex2bin(File::read_file($data["result"]))));
                break;
            default:
                $data = unserialize($data["result"]);
                break;
        }
    }

//初始化数据库连接
    public static function db($dbname = 'master') {
        if (is_array($dbname)) {
            $db = new Database($dbname);
        } else {
            $db = new Database(self::$config->get("db[{$dbname}]"));
        }

        $db->connect();
        return $db;
    }

    /*
     * 错误捕捉
     */

    public function handleFatal() {
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
                    if (isset($server['REQUEST_URI'])) {
                        $log .= '[QUERY] ' . $server['REQUEST_URI'];
                    }
                    Log::write_error("system error: $log");
            }
        }
    }

}
