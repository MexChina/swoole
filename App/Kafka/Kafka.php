<?php
/**
 * 刷库往kafka里面写数据
 * 'dev'=>'192.168.1.71:9092,192.168.1.72:9092',
 * 'pro'=>'hadoop1:9092,hadoop2:9092,hadoop3:9092,hadoop4:9092,hadoop5:9092,hadoop7:9092'
 * id 区间分段法开辟 多个进程块 
 */
namespace Swoole\App\Kafka;
use Swoole\Core\Log;
use \RdKafka\Producer;
use \RdKafka\Conf;
use \RdKafka\TopicConf;
use \Exception;
class Kafka extends \Swoole\Core\App\Controller{


    private $process_total=240;     //总进程数  2的幂数 db_total的倍数
    private $db_total=24;           //数据库的个数
    private $db_name;               //当前进程处理的库名
    private $start_id;              //当前进程区间开始id
    private $end_id;                //当前进程区间结束id
    private $data_total;            //当前进程区间数据总量


    private $current_i=0;
    private $param;

    private $algorithms=array(
         //'cv_trade',
        // 'cv_title',
        'cv_tag',
        // 'cv_entity',
        // 'cv_education',
        // 'cv_feature',
        // 'cv_workyear',
        // 'cv_quality',
        // 'cv_language',
        // 'cv_resign',
        // 'cv_source'
    );

    
    public function init(){
        //创建连接
        $this->conn();
        $this->dispatch();
        $this->param = new Algorithm();

    }


    /**
     * 24个库 主表进程 
     * @return [type] [description]
     */
    public function index(){
        $log_start_time = number_format(microtime(true), 8, '.', '');
        $result = $this->read_db->query("select count(1) as `total` from resumes where id >= {$this->start_id} and id <= {$this->end_id}")->fetch();
        $this->data_total = $result['total'];
        Log::write_log("{$this->db_name} total:{$this->data_total}");
        $result = $this->read_db->query("select id,compress from resumes_extras where id >= {$this->start_id} and id <= {$this->end_id}",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
            $this->algorithm($row);
            $this->current_i++;
            if($this->current_i%100 == 0){
                $percent = sprintf("%.2f", $this->current_i/$this->data_total*100);
                Log::write_log("{$this->db_name} {$this->current_i}/{$this->data_total} {$percent}%");
            }

            if($this->current_i % 100000 == 0){
                $this->conn();
            }
        }
        $result->close();
        $runtime = number_format(microtime(true), 8, '.', '') - $log_start_time . 's';
        Log::write_log("{$this->db_name} total:{$this->data_total} compelete refresh use $runtime...");
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
        foreach($this->algorithms as $field){
            $result = $this->param->$field($compress);
            if($result !== false){
                $data[$field] = $result;
            }
        }

        if(empty($data)){
            error_log(date('Y-m-d H:i:s')."\t".$resume_id."\n",3,"/opt/log/param_error.".date('Ymd'));
            return;
        }

        $data['resume_id'] = $resume_id;
        $data_str = json_encode($data,JSON_UNESCAPED_UNICODE);
        // Log::write_log($data_str);
        // error_log(date('Y-m-d H:i:s')."\t".$data_str."\n",3,'/opt/log/kafa_data'.date('Ymd'));
        $this->send($data_str);
    }

    public function send($str){
        try{
            $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $str);
            $this->rk->poll(0);

            while ($this->rk->getOutQLen() > 0) {
                $this->rk->poll(50);
            }
        }catch(Exception $e){
            $this->conn();
            $this->send($str);
        }
    }

    public function conn(){
        $start_time = number_format(microtime(true), 8, '.', '');

        $rcf = new Conf();
        $rcf->set('group.id', 'resume_arth_test');
        $rcf->set('session.timeout.ms', 3600000);
        $cf = new TopicConf();
        $cf->set('offset.store.method', 'broker');
        $cf->set('auto.offset.reset', 'smallest');
        $cf->set('request.timeout.ms', 900000);
        $cf->set('message.timeout.ms', 900000);
        

        $this->rk = new Producer($rcf);
        $this->rk->setLogLevel(LOG_DEBUG);
        $this->rk->addBrokers('hadoop1:9092,hadoop2:9092,hadoop3:9092,hadoop4:9092,hadoop5:9092,hadoop7:9092');
        $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
        $this->topic = $this->rk->newTopic('resume_arth_test');
        Log::write_log("kafak hosts {$this->kafka_host['pro']} connection success use:$runtime ...");
    }

    /**
     * 分段
     * @return [type] [description]
     */
    public function dispatch(){
        //可以将【min_id,max_id】区间的数据分成 proce_num 份
        $proce_num = $this->process_total/$this->db_total;

        //要被分割的次数总和  二分法分割 取平方根
        $split_total = ceil(log($proce_num,2));

        //取出当前进程所要处理的库名
        for($i=0;$i<$this->process_total;$i++){
            $w[$i] = $i%$this->db_total;
            if($i == $this->swoole->worker_id){
                $db_num = $w[$i];
                $this->db_name = "icdc_".$db_num;
            }           
        }
        
        //连接数据库
        $this->read_db = $this->db($this->db_name);

        //取出当前数据库中最大id和最小id 并进行区间分配 取出当前进程所需要处理的区间id
        $max_arr = $this->read_db->query("select max(id) as id from resumes limit 1")->fetch();    //获取最大的id
        $min_arr = $this->read_db->query("select min(id) as id from resumes limit 1")->fetch();    //获取最小的id

        $tmp[0][]=array('min'=>$min_arr['id'],'max'=>$max_arr['id']);
        for($i=1;$i<=$split_total;$i++){
            foreach($tmp[$i-1] as $k){                  //取上一次的处理结果
                $mid = ceil(($k['min']+$k['max'])/2);   //将上一次区间的中间值取出来
                $tmp[$i][]=array('min'=>$k['min'],'max'=>$mid);
                $tmp[$i][]=array('min'=>$mid+1,'max'=>$k['max']);
            }
            unset($tmp[$i-1]);
        }

        $limit = $tmp[$split_total]; //取最后一次，为完整的区间
        unset($tmp);

        $db_arr = [];
        foreach($w as $k=>$s){
            if($s == $db_num){
                $db_arr[] = $k;
            }
        }
        
        foreach ($db_arr as $key => $db_i) {
            if($db_i == $this->swoole->worker_id){
                $this->start_id = $limit[$key]['min'];
                $this->end_id = $limit[$key]['max'];
                break;
            }
        }
        unset($limit,$db_arr);
        Log::write_log("{$this->db_name} id∈[{$this->start_id},{$this->end_id}] start refresh...");
    }


    public function __destruct(){
        $this->read_db->close();
        Log::write_log("rk and read_db close...");
    }
}