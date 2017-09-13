<?php

/**
 * 处理algorithm_jobs数据脚本
 * for production
 */
class Algorithm {
    private $db; //数据库对象
    private $gm; //gearman对象
    private $count; //进程数量
    private $chan; //管道对象
    private $time_flag = false;

    public function __construct($process_num) {
        $this->count = $process_num;
    }

    /**
     * 日志记录
     * @param  [mixed] $msg    记录的信息
     * @param  [string] $unique 全局logid
     * @return [type]         [description]
     */
    public function log($msg, $unique = null) {
        if (is_null($unique)) {
            $unique = getmypid();
        }
        $now = date('Y-m-d H:i:s');
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        return error_log("{$now}\t$unique\t{$msg}\r\n", 3, "/opt/log/icdc_queue." . date
            ('Y-m-d'));
    }

    /**
     * mysql数据库连接
     */
    public function connect() {
        $count = 1;
        while (1) {
            $count++;
            $this->db = new mysqli("192.168.8.130", "icdc", "rg62fnme2d68t9cmd3d",
                "icdc_allot", 3306);
            if ($this->db->connect_error) {
                $this->log("第 {$count} 次连接mysql(dbhost:{$host},dbport:{$port},dbpwd:{$password},dbuser:{$user},dbname:{$database})失败: " .
                    mysqli_connect_error(), 1);
                if ($count >= 3) {
                    return false;
                }
                sleep(rand(1, 5));
            }
            else {
                break;
            }
        }
        $this->db->set_charset("utf8");
        return true;
    }

    /**
     * 数据库query
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function query($sql) {
        $result = false;
        $r = true;
        if (empty($this->db)) {
            $this->checkConnection();
        }
        for ($i = 0; $i < 2; $i++) {
            try {
                $result = $this->db->query($sql);
                if (empty($result)) {
                    if ($this->db->errno == 2013 || $this->db->errno == 2006 || $this->db->errno ==1053) {
                        $r = $this->db->checkConnection();
                        if ($r === true) {
                            continue;
                        }
                    }elseif ($this->db->errno == 2014) {
                        $this->log("Commands out of sync");
                        $r = $this->checkConnection();
                        if ($r === true) {
                            continue;
                        }
                    }
                    $this->log($sql . " query false i:$i");
                }else {
                    break;
                }

            }catch (exception $exc) {
                $this->log($exc->getTraceAsString());
            }
        }
        return $result;
    }

    /**
     * 检查数据以及数据库重连
     * @return [type] [description]
     */
    public function checkConnection() {
        if (empty($this->db)) {
            return $this->connect();
        }
        if (!@$this->db->ping()) {
            $this->log("{$this->host}:{$this->port} reconnect success");
            $this->db->close();
            return $this->connect();
        }else {
            return true;
        }
    }

    /**
     * 接口请求
     * @param  [type] $job_id [description]
     * @return [type]         [description]
     */
    public function api($job_id) {
        if (empty($this->gm)) {
            $this->gm = new GearmanClient();
            $this->gm->addServers("192.168.8.39:4731");
        }
        $unique = getmypid() . uniqid('_AlgorithmJobs');
        $param['header'] = array(
            'product_name' => 'AlgorithmJobs',
            'uname' => 'AlgorithmJobs',
            'ip' => '192.168.8.38',
            'log_id' => $unique,
            'appid' => 999,
            );
        $param['request'] = array(
            'c' => 'resumes/Logic_algorithm',
            'm' => 'process_resume',
            'p' => array('id' => $job_id, ),
            );
        $this->gm->doNormal('icdc_refresh', json_encode($param), $unique);
    }

    /**
     * 将数据读出来放进共享管道
     * @param  [type] $time [description]
     * @return [type]       [description]
     */
    public function read($time) {
        $result = $this->query("select resume_id,created_at from algorithm_jobs where created_at >= '$time' order by created_at asc limit 1000")->fetch_all(MYSQLI_ASSOC);
        if(empty($result)){
            return $time;
        }
        
        foreach ($result as $row) {
            while($this->chan->push($row['resume_id']) === false){
                $status = $this->chan->stats();
                $this->log("channel full status：" . json_encode($status));
                sleep(5);
            }
            $time = $row['created_at'];
        }
        return $time;        
    }

    /**
     * 主进程脚本启动入口
     * @return [type] [description]
     */
    public function run() {
        swoole_set_process_name('AlgorithmJobs');
        swoole_process::daemon(true, false);
        $this->chan = new Swoole\Channel(1024 * 256);

        for ($i = 1; $i <= $this->count; $i++) {
            $this->CreateProcess();
        }
        
        swoole_timer_tick(1000, function () {
            $res = $this->query("select created_at from algorithm_jobs order by created_at asc limit 1")->fetch_assoc();
            if(empty($res)){
                $this->log("no data need process");
                return;
            }
            
            if($this->time_flag === false){
                $this->time_flag = $res['created_at'];
            }else{
                if(strtotime($this->time_flag) - strtotime($res['created_at']) > 3600){
                    $this->time_flag = $res['created_at'];
                }
            }
            
            $this->time_flag = $this->read($this->time_flag);
            $status = $this->chan->stats();
            $status['created_at'] = $this->time_flag;
            $this->log($status);
        });

        swoole_process::signal(SIGCHLD, function ($sig) {
            while ($ret = swoole_process::wait(false)) {
                $this->log("AlgorithmJobs PID={$ret['pid']}"); }
        }
        );
    }

    /**
     * 创建子进程并分配子进程作业
     */
    public function CreateProcess() {
        $process = new swoole_process(function () {
            swoole_set_process_name('AlgorithmJobs');
            while (1) {
                $id = $this->chan->pop(); 
                if ($id !== false) {
                    $this->api($id); 
                }else{
                    sleep(1);
                }
            }

        }, false, 1);
        $idd = $process->start();
    }

    public function __destruct() {
        $this->db->close();
    }
}

if (!isset($argv[1])) {
    die("please input start or stop!\n");
}
switch ($argv[1]) {
    case 'start':
        $process_num = isset($argv[2]) ? (int)$argv[2] : 20;
        $obj = new Algorithm($process_num);
        $obj->run();
        break;
    case 'stop':
        exec("ps -aux | grep AlgorithmJobs | grep -v grep | awk '{print $2}' | xargs kill -15");
        break;
    default:
        die("please input start or stop!\n");
        break;
}
