<?php

namespace Swoole\Core\Lib;

use \swoole_client;
use Swoole\Core\Helper\File;
use Swoole\Core\Log;

/**
 * 
 * 异步mysql客户端类，如下示例：
 * $client = new Scmysql("127.0.0.1", 13306, 'icdc_0');
 * $client->connect();
 * $sql = "show databases";
 * $client->query($sql, function($result, $client) {
 *     var_dump($result);
 * });
 * 在回调函数的reslut变量中，将直接返回mysqli::fetch_all(MYSQLI_ASSOC);所得到的值，否则返回错误代码。
 * 
 */
class Scmysql {

    /**
     * swoole客户端对象,用来保存对异步mysql的socket连接
     *
     * @var swoole_client
     */
    private $client;
    private $host;
    private $port;
    private $timeout = 0.2;
    private $data = ""; //接收到的数据
    public $result = ''; //执行结果
    private $runtime = 0;
    private $resclose = false; //是否已经发送过关闭请求 
    public static $clientcount = 0;  //客户端数量
    private $funcids = array(); //管理客户端查询回调函数
    private $querys = array();  //管理正在执行查询的sql队列
    private $sqls = array(); //管理未处理查询队列
    public $isconnect = false; //连接状态
    private $dbname;
    private $prefix = array(); //表前缀
    private $rev_length = 0; //当前收到的数据包整包长度

    function __construct($host = "127.0.0.1", $port = "10000", $dbname = "") {
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->connect();
    }

    function onConnect(swoole_client $cli) {
        //echo "客户端已经连接......\n";
        $this->runtime = time();
        $this->isconnect = $this->client->isConnected();
        if ($this->isconnect && !empty($this->sqls)) {
            foreach ($this->sqls as $key => $sql) {
                $this->senddata($sql['sql'], $sql['func']);
                unset($this->sqls[$key]);
            }
        }
    }

    function onReceive(swoole_client $cli, $sdata) {
        $this->rev_length += strlen($sdata);
        $this->data = $this->data . $sdata;
        if (strpos("CLOSEEND\r\n", $sdata) !== false) {
            $cli->close();
            echo "client closed ......";
            unset($this);
            exit;
        }
        if (!$this->data) {
            return;
        }
        $results = array();
        $results = $this->dealInput($this->data);
        if ($results === FALSE) {
            return;
        }
        Log::writelog("[dbname:{$this->dbname}]reseive data length: {$this->rev_length}");
        if (!empty($results)) {
            foreach ($results as $sqlid => $result) {
                $code = $result['code'];
                $sqlid = intval(substr($sqlid, 4));
                if ($code != 0) {
                    Log::writelog("error:{$result['result']}, sql:" . substr($this->querys[$sqlid], 0, 60) . "......");
                    $writelogresult = File::write_file(SWOOLE_ROOT_DIR . "/cache/sql/error_sql.log", ($this->querys[$sqlid] . "\n"), "a");
                } else {
                    switch ($sqlid) {
                        case 0:
                            break;
                        default:
                            //echo "result return[{$this->dbname}:$sqlid]:{$this->querys[$sqlid]}\n";
                            if (@$this->funcids[$sqlid]) {
                                call_user_func($this->funcids[$sqlid], $result, $this);
                            }
                            unset($this->funcids[$sqlid]);
                            unset($this->querys[$sqlid]);
                            break;
                    }
                }
            }
        } else {
            Log::writelog("explain bytes data error,reseive data length: {$this->rev_length}");
        }
        $this->rev_length = 0;
    }

    function onError(swoole_client $cli) {
        $this->result = "error......<br/>";
    }

    function onClose(swoole_client $cli) {
        self::$clientcount--;
        unset($this->client);
    }

    function close() {
        $this->client->send("CLOSEEND\r\n");
        $this->resclose = true;
    }

    //发包
    public function query($sql, $func = "") {
        if ($this->isconnect) {
            $this->senddata($sql, $func);
        } else {
            $this->sqls[] = ["sql" => $sql, "func" => $func];
        }
    }

    //设置需要连接的数据名
    public function dbname($dbname) {
        $this->dbname = $dbname;
    }

    private function senddata($sql, $func = "") {
//      $this->client->send($req_package);
//      echo "send query $sql\n";
        end($this->funcids);
        $id = key($this->funcids) + 1;
        //$id = 23;
        $this->funcids[$id] = $func;
        $this->querys[$id] = $sql;
//echo "send sql[{$this->dbname}:$id]:$sql\n";
        $resouce = "|-" . $this->dbname . "-|" . gzcompress($sql, 9);
        $total_len = pack('N2', $id, strlen($resouce) + 8);
        $req_package = $total_len . $resouce;
        $this->client->send($req_package);
    }

    //获取表前缀
    private function getprefix($client) {
        $resouce = "|-" . $this->dbname . "-|pre";
        $total_len = pack('N2', 0, strlen($resouce) + 8);
        $req_package = $total_len . $resouce;
        $client->send($req_package);
    }

//处理接收到的数据包
    private function dealInput(& $recv_buffer) {
        $retrun = $retrun_next = array();
        // 接收到的数据长度
        $recv_len = strlen($recv_buffer);
        // 如果接收的长度还不够四字节，那么要等够四字节才能解包到请求长度
        if ($recv_len < 8) {
            // 不够四字节，等够四字节
            return false;
        }
        // 从请求数据头部解包出整体数据长度
        $unpackbuffer = substr($recv_buffer, 0, 8);
        try {
            $unpack_data = unpack('N2int', $unpackbuffer);
        } catch (Exception $exc) {
            var_dump($exc->getTraceAsString());
            $recv_buffer = "";
            return false;
        }
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
    private function depackage($package) {
        $package = substr($package, 8);
        $response = unserialize(gzuncompress($package));
        return $response;
    }

    //获取补全前缀的表名
    public function table($tablename) {
        return $this->prefix . $tablename;
    }

    function connect() {
        //echo "开始创建连接......<br/>";
        //判断mysql线程池是否可以连接成功，并获取相关表的配置文件。
        $testclient = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        if ($testclient->connect($this->host, $this->port, $this->timeout) === false) {
            return false;
        } else {
            $this->getprefix($testclient);
            $data = $testclient->recv();
            $this->prefix = substr($data, 8);
            $testclient->close();
        }

        $this->client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC); //异步非阻塞
        $this->client->on('connect', array($this, 'onConnect'));
        $this->client->on('receive', array($this, 'onReceive'));
        $this->client->on('error', array($this, 'onError'));
        $this->client->on('close', array($this, 'onClose'));
        $this->client->set(array(
            'package_max_length' => 1024 * 1024 * 2, //协议最大长度
            'socket_buffer_size' => 1024 * 1024 * 8, //8M缓存区
        ));
        $return = $this->client->connect($this->host, $this->port, $this->timeout, 1);
        if (!$return) {
            return false;
        }
        self::$clientcount++;
        return TRUE;
    }

}

//$client = new Scmysql("127.0.0.1", 13306, 'newbi');
//$sql = "INSERT INTO base_common (`resume_id`,  `name`,  `gender`,  `birth`,  `marital`,  `is_fertility`,  `is_house`,  `account_province`,  `account`,  `address_province`,  `address`,  `work_experience`,  `current_status`,  `basic_salary_from`,  `basic_salary_to`,  `annual_salary_from`,  `annual_salary_to`,  `expect_annual_salary`) VALUES ('135296', '叶成玲', 'F', '', '', 'U', 'U', '0', '0', '24', '268', '1', '3', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135312', '徐五爱', 'M', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135320', '吴庆怀', 'U', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135328', '赵世栋', 'M', '', '', 'U', 'U', '0', '0', '11', '106', '9', '3', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135344', '蒋小龙', 'M', '', 'N', 'N', 'Y', '11', '0', '11', '110', '3', '4', '4.0', '4.0', '112.0', '112.0', '0.0'), ('135352', '王昌涛', 'M', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135360', '鲁厂长', 'M', '', 'Y', 'Y', 'Y', '24', '278', '16', '174', '12', '3', '0.0', '0.0', '100.0', '100.0', '0.0'), ('135384', '沈枷育', 'F', '', '', 'U', 'U', '7', '0', '20', '231', '12', '4', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135392', '宋先生', 'U', '', '', 'U', 'U', '0', '0', '16', '168', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135408', '于峰', 'U', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135448', '姚文韬', 'M', '', 'N', 'N', 'N', '17', '0', '2', '33', '2', '4', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135456', '武文锵', 'U', '', '', 'U', 'U', '0', '0', '2', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135464', '杜益凡', 'M', '', 'N', 'N', 'N', '11', '107', '2', '33', '4', '4', '0.0', '0.0', '250.0', '250.0', '0.0'), ('135472', '朱苏', 'M', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135504', '黄静', 'F', '', '', 'U', 'U', '0', '0', '2', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135512', '孙杰伟', 'M', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135520', '邢云龙', 'U', '', '', 'U', 'U', '0', '0', '2', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135544', '张晶白', 'M', '198111', '', 'U', 'U', '20', '231', '0', '0', '2', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135552', '聂士海', 'M', '', '', 'N', 'N', '9', '93', '2', '0', '2', '4', '8.0', '8.0', '96.0', '96.0', ''), ('135560', '孙宇(网页搜索测试部)', 'M', '', '', '', '', '0', '0', '2', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', ''), ('135568', '', 'M', '1981年8月', 'Y', 'U', 'U', '4', '41', '2', '33', '10', '2', '15', '25', '0', '0', ''), ('135576', '路 Louhan', 'M', '', 'Y', 'Y', 'N', '3956', '3957', '2', '33', '5', '4', '0.0', '0.0', '150.0', '150.0', '150.0'), ('135600', '张寅峰', 'M', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135616', '韩露', 'M', '', '', 'U', 'U', '18', '205', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135632', '黄琨', 'M', '', '', 'U', 'U', '0', '0', '2', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135648', 'Ada.Yang', 'F', '', '', 'U', 'U', '0', '0', '2', '33', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135664', '吴锡', 'U', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135672', '李武魁', 'M', '', '', 'U', 'U', '0', '0', '2', '0', '0', '4', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135688', '韩海', 'M', '', '', '', '', '0', '0', '2', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', ''), ('135704', '李  超', 'M', '', '', 'U', 'U', '0', '0', '5', '46', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135720', '任云忠', 'M', '', '', 'U', 'U', '23', '0', '23', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0'), ('135728', '唐径威', 'M', '', 'Y', 'N', 'N', '19', '0', '20', '231', '5', '4', '5.0', '5.0', '60.0', '60.0', '0.0'), ('135744', '林桂光', 'M', '', '', 'U', 'U', '0', '0', '0', '0', '0', '0', '0.0', '0.0', '0.0', '0.0', '0.0')";
//$client->connect();
//$client->query($sql, function($result, $client) {
//    var_dump($result);
//    $client->close();
//    unset($client);
//    exit;
//});


//$client->query("SELECT ra.*, re.compress FROM resumes AS r LEFT JOIN resumes_algorithms AS ra ON ra.id=r.id LEFT JOIN resumes_extras AS re ON re.id=r.id WHERE r.id>(SELECT id FROM resumes where is_deleted='N' LIMIT 22000,1) LIMIT 1000", function($result, $client) {
//    var_dump($result);
//});
//SELECT ra.*, re.compress FROM resumes AS r LEFT JOIN resumes_algorithms AS ra ON ra.id=r.id LEFT JOIN resumes_extras AS re ON re.id=r.id WHERE r.id>(SELECT id FROM resumes where is_deleted='N' LIMIT 90000,1) LIMIT 100