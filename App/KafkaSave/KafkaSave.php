<?php
/**
 * 刷库往kafka里面写数据
 */
namespace Swoole\App\KafkaSave;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
class KafkaSave extends \Swoole\Core\App\Controller{

 
    private $algorithm_field=['cv_trade','cv_title','cv_tag','cv_entity','cv_education','cv_feature','cv_workyear','cv_quality','cv_language','cv_resign','cv_source'];

    private $redis_config=[
        'dev'=>["192.168.1.201:7000","192.168.1.201:7001","192.168.1.201:7002",'192.168.1.201:7003','192.168.1.201:7004','192.168.1.201:7005'],
        'test'=>["10.9.10.6:7000","10.9.10.6:7001","10.9.10.6:7002",'10.9.10.6:7003','10.9.10.6:7004','10.9.10.6:7005'],
        'pro'=>['192.168.8.116:7105','192.168.8.115:7105','192.168.8.114:7105','192.168.8.113:7105','192.168.8.116:7106','192.168.8.115:7106',
                '192.168.8.114:7106','192.168.8.113:7106','192.168.8.116:7107','192.168.8.115:7107','192.168.8.114:7107','192.168.8.113:7107',
                '192.168.8.116:7108','192.168.8.115:7108','192.168.8.114:7108','192.168.8.113:7108']
    ];

    
    public function init(){

        $this->db_name = "master_icdc_".$this->swoole->worker_id;
        $this->read_db = $this->db($this->db_name);
        $this->redis = new RedisCluster(NULL,$this->redis_config['test']);

    }

    /**
     * 24个库 主表进程 
     * @return [type] [description]
     */
    public function index(){
        $worker = new Worker("icdc_algorithm");
        $worker->worker(function($request){
            if(empty($request['m']) || empty($request['p'])){
                return array('err_no'=>1,'err_msg'=>'Error Processing Request m or p','results'=>array());
            }
            $m = strtolower($request['m']);
            try{
                switch ($m) {
                    case 'get':         //查算法数据
                        $results = $this->mapping_info($request['p']);
                        break;
                    case 'set':        //写算法数据
                        $results = $this->mapping_save($request['p']);
                        break;
                    default:
                        $results = array('err_no'=>1,'err_msg'=>'Error Processing Request m','results'=>array());
                        break;
                }
            }catch(Exception $e){
                $results = array('err_no'=>$e->getCode(),'err_msg'=>$e->getMessage(),'results'=>array());
            }
            return array('results'=>$results);
        });
    }

    /**
     * 算法参数
     * @return [type] [description]
     */
    public function algorithm($extra){
        if(empty($extra)){
            // Log::write_log("resumes_extras 不存在");
            return;
        }

        $resume_id = (int)$extra['id'];
        if($resume_id <= 0){
            // Log::write_log("resumes_extras 不存在");
            return;
        }

        $compress = json_decode(gzuncompress($extra['compress']), true);
        if(empty($compress)){
            // Log::write_log("$resume_id compress 数据损坏");
            return;
        }

        if(empty($compress['basic']['id'])){
            $compress['basic']['id'] = $resume_id;
        }

        $data=[];
        foreach($this->algorithm_field as $field){
            if(isset($this->kafka_config[$field])){
                $result = $this->algorithm->$field($compress);
                if($result !== false){
                    $data[$field] = $result;
                }
            }
        }

        if(empty($data)){
            error_log(date('Y-m-d H:i:s')."\t".$resume_id."\n",3,"/opt/log/param_error.".date('Ymd'));
            return;
        }

        $data['resume_id'] = $resume_id;


        $data_str = json_encode($data,JSON_UNESCAPED_UNICODE);
        // error_log(date('Y-m-d H:i:s')."\t".$data_str."\n",3,'/opt/log/kafa_data'.date('Ymd'));
        $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $data_str);
        $this->rk->poll(0);

        while ($this->rk->getOutQLen() > 0) {
            $this->rk->poll(50);
        }
    }


    /**
     * 将kafka的配置文件写到远程服务器上
     * @return [type] [description]
     */
    private function remote(){
        $start_time = number_format(microtime(true), 8, '.', '');
        $connection = ssh2_connect($this->host, 22);
        if(!$connection){
            Log::write_log("connection to {$this->host}:22 failed");
            return false;
        }
        $auth_methods = ssh2_auth_none($connection, $this->user);
        if(ssh2_auth_password($connection, $this->user, $this->passswd)) {  
            Log::write_log("{$this->host} {$this->user} login success...");
        }

        $kafka_config = json_encode($this->kafka_config);
        file_put_contents("/opt/log/kafalog",$kafka_config);
        $flag = ssh2_scp_send($connection,"/opt/log/kafalog",$this->config_file, 0644);  //默认权限为0644，返回为bool
        unlink("/opt/log/kafalog");
        $status = $flag ? "success" : "failed";
        $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
        Log::write_log("kafka config write {$this->host} $status use:$runtime...");
    }

    /**
     * kafka 连接
     * @return [type] [description]
     */
    private function kafka(){
        $start_time = number_format(microtime(true), 8, '.', '');
        $this->rk = new Producer();
        $this->rk->setLogLevel(LOG_DEBUG);
        $this->rk->addBrokers($this->kafka_host['dev']);
        $this->topic = $this->rk->newTopic($this->kafka_config['topic']);
        $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
        Log::write_log("kafak hosts {$this->kafka_host['dev']} connection success use:$runtime ...");
    }


    public function __destruct(){
        $this->read_db->close();
        Log::write_log("rk and read_db close...");
    }
}