<?php
/**
 * 自动分配多进程刷库的程序脚本
 */

namespace Swoole\App\Refresh88;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
use \RedisCluster;
class Refresh88 extends \Swoole\Core\App\Controller{

    private $process_total=8;     //总进程数  2的幂数 db_total的倍数
    private $db_total=4;           //数据库的个数
    private $db_name;               //当前进程处理的库名
    private $start_id;              //当前进程区间开始id
    private $end_id;                //当前进程区间结束id
    private $data_total;            //当前进程区间数据总量
    private $page_size=100;         //多少条数据一次回写
    private $page_box=[];           //存放page_size条数据的盒子
    private $current_i=0;           //当前正取第几条数据
 


    public function init(){}
    public function start(){
        Log::write_log(" refresh...");
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
        $this->read_db = $this->db("master_".$this->db_name);
        $this->write_db = $this->db("master_".$this->db_name);
        if(in_array('cv_resign',$this->refresh_field)){
            $this->bi_data = $this->db("bi_data");
        }

        //连接redis
        $this->redis = new RedisCluster(NULL,$this->redis_config['test']);
        
        //连接算法worker服务器
        foreach($this->refresh_servers as $worker_name){
            $this->apiinit($worker_name);
        }

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

 

    

    public function index(){
       Log::write_log("index refresh...");

        //$this->start();

        //统计总条数
        // $result = $this->read_db->query("select count(1) as total from resumes_extras where id >= {$this->start_id} and id <= {$this->end_id}")->fetch();
        // $this->data_total = $result['total'];

        // //根据区间取数据
        // $result = $this->read_db->query("select id,compress from resumes_extras where id >= {$this->start_id} and id <= {$this->end_id}",MYSQLI_USE_RESULT);
        
        // while ($row=$result->fetch_assoc()){
        //     $this->algorithm($row);
        //     $this->current_i++;
        // }
        // $result->close();
        // Log::write_log("{$this->db_name} id∈[{$this->start_id},{$this->end_id}] total:{$this->current_i} compelete refresh...");
    }





    private function access($api,$msg,$type="RSP:",$time=''){
        error_log(date('Y-m-d H:i:s')."\t{$this->swoole->worker_id}\t$type\t".json_encode($msg,JSON_UNESCAPED_UNICODE)."\t$time\n",3,"/opt/log/{$api}_access.".date('Y-m-d'));
    }

    public function __destruct(){
        $this->read_db->close();
        $this->write_db->close();
        if(in_array('cv_resign',$this->refresh_field)){
            $this->bi_data->close();
        }
    }
}