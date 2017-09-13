<?php
/**
 * 自动分配多进程刷库的程序脚本
 * 
 */

namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
use Swoole\Core\Helper\System;
use \RedisCluster;
class Refresh extends \Swoole\Core\App\Controller{

    private $process_total=240;     //总进程数  2的幂数 db_total的倍数
    private $db_total=24;           //数据库的个数
    private $db_name;               //当前进程处理的库名
    private $start_id;              //当前进程区间开始id
    private $end_id;                //当前进程区间结束id 
    private $data_total;            //当前进程区间数据总量
    private $page_size=1000;         //多少条数据一次回写
    private $page_box=[];           //存放page_size条数据的盒子
    private $current_i=0;           //当前正取第几条数据
 
    /**
     * redis配置
     * @var [type]
     */
    private $redis_config=[
        'dev'=>["192.168.1.201:7000","192.168.1.201:7001","192.168.1.201:7002",'192.168.1.201:7003','192.168.1.201:7004','192.168.1.201:7005'],
        'test'=>["10.9.10.6:7000","10.9.10.6:7001","10.9.10.6:7002",'10.9.10.6:7003','10.9.10.6:7004','10.9.10.6:7005'],
        'pro'=>['192.168.8.116:7105','192.168.8.115:7105','192.168.8.114:7105','192.168.8.113:7105','192.168.8.116:7106','192.168.8.115:7106',
                '192.168.8.114:7106','192.168.8.113:7106','192.168.8.116:7107','192.168.8.115:7107','192.168.8.114:7107','192.168.8.113:7107',
                '192.168.8.116:7108','192.168.8.115:7108','192.168.8.114:7108','192.168.8.113:7108']
    ];


    //要刷库的字段
    private $refresh_field=array(   
        //'cv_trade',
        // 'cv_tag',
        //'cv_entity',
        //'cv_education',     //学历识别
        //'cv_resign',      //离职率
        'cv_feature'

    );

    //要连接的worker的名称
    private $refresh_servers=array(
        //'es_servers',
        //'fwdindex_service_online',
        // 'icdc_online',
        // 'tag_predict', //cv_tag 的worker
        'rs_feature_svr_online_new_format'
    );

    //刷库白名单字典
    private $algorithm_field=[
        'cv_trade','cv_title','cv_tag','cv_entity','cv_education','cv_feature','cv_workyear','cv_quality','cv_language','cv_resign','cv_source'
    ];

    private $is_update_resume = false;          //是否更新主表
    private $is_update_resume_extra = false;    //是否更新压缩包表

    public function init(){}
    public function start(){

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
        $this->read_db = $this->db("slave_".$this->db_name);
        $this->write_db = $this->db("master_".$this->db_name);
        
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

    public function times(){
        $com_workid = [];
        $allow_workid = [0,2,3,4,5,9];
        $start_time = strtotime('2017-07-18 23:00:00');
        if(!in_array($this->swoole->worker_id,$allow_workid)){
            if(time() < $start_time){
                sleep(60);
                Log::write_log($this->swoole->worker_id." will start at $start_time sleep 60");
                $this->times();
            }
        }
        if(in_array($this->swoole->worker_id,$com_workid)){
            Log::write_log($this->swoole->worker_id." compelete...");
            sleep(999999999);
        }
    }

 

    

    public function index(){
        // $this->times();
        $this->start();
        $log_start_time = number_format(microtime(true), 8, '.', '');

        //统计总条数
        $result = $this->read_db->query("select count(1) as total from resumes where id >= {$this->start_id} and id <= {$this->end_id}")->fetch();
        $this->data_total = $result['total'];

        // //根据区间取数据
        $result = $this->read_db->query("select id,compress from resumes_extras where id >= {$this->start_id} and id <= {$this->end_id}",MYSQLI_USE_RESULT);
        $istart_time = number_format(microtime(true), 8, '.', '');
        while ($row=$result->fetch_assoc()){
            $this->algorithm($row);
            $this->current_i++;
            if($this->current_i%$this->page_size === 0){
                $percent = sprintf("%.2f", $this->current_i/$this->data_total*100);
                $run = number_format(microtime(true), 8, '.', '') - $istart_time . 's';
                $mem = System::get_used_memory();
                Log::write_log("{$this->db_name} id:{$resume_id}∈[{$this->start_id},{$this->end_id}] {$this->current_i}/{$this->data_total} {$percent}% $run $mem");
                $istart_time = number_format(microtime(true), 8, '.', '');
            }
        }
        $percent = sprintf("%.2f", $this->current_i/$this->data_total*100);
        $run = number_format(microtime(true), 8, '.', '') - $istart_time . 's';
        $mem = System::get_used_memory();
        Log::write_log("{$this->db_name} id:{$resume_id}∈[{$this->start_id},{$this->end_id}] {$this->current_i}/{$this->data_total} {$percent}%  $run $mem");
        $result->close();
        $runtime = number_format(microtime(true), 8, '.', '') - $log_start_time . 's';
        Log::write_log("{$this->db_name} id∈[{$this->start_id},{$this->end_id}] total:{$this->current_i} compelete $runtime");
    }

    /**
     * 
     * @param  [type] $info [description]  array(id,compress)
     * @return [type]       [description]
     */
    private function algorithm($extra){
        $this->page_box[]=1;
        $redis_data=[]; 
        $save_data=[];
        $is_update_resume_extra=false;
        

        if(empty($extra)){
            Log::write_log("resumes_extras 不存在");
            return;
        }

        $resume_id = (int)$extra['id'];
        if($resume_id <= 0){
            Log::write_log("resumes_extras 不存在");
            return;
        }

        $compress = json_decode(gzuncompress($extra['compress']), true);
        if(empty($compress)){
            Log::write_log("$resume_id compress 数据损坏");
            return;
        }

        if($compress['basic']['is_deleted'] == 'Y'){
            return;
        }

        if(empty($compress['basic']['id'])){
            $compress['basic']['id'] = $resume_id;
            $this->is_update_resume_extra = true;
        }

        //查询简历基本信息  es服务需要
        //$resume_info = $this->read_db->query("select work_experience,resume_updated_at,updated_at,name,is_deleted from resumes where id=$resume_id")->fetch();

        
        //查询简历算法信息  正排服务和离职率需要
        // $get_str='';
        // foreach($this->algorithm_field as $s_field){
        //     $get_str.="column_get(data,'$s_field' as char) as $s_field,";
        // }
        // $get_str = rtrim($get_str,',');
        // $resume_algorithm = $this->read_db->query("select $get_str,updated_at from algorithms where id=$resume_id")->fetch();

        // if(strtotime($resume_algorithm['updated_at']) >= strtotime("2017-07-07")){
        //     return;
        // }

       

        /***********************************************************************************************************
         * 职能
         ***********************************************************************************************************/
        if(in_array('cv_tag',$this->refresh_field)){
            $res = $this->cv_tag($compress);
            if($res === null){
                return;
            }
            $redis_data['cv_tag'] = $resume_algorithm['cv_tag'] = $save_data['cv_tag'] = $res;
        }

        if(in_array('cv_feature',$this->refresh_field)){
            $res = $this->cv_feature($compress);
            if($res === null){
                return;
            }
            $redis_data['cv_feature'] = $resume_algorithm['cv_feature'] = $save_data['cv_feature'] = $res;
        }

        //====================================通知es服务=======================================================
        //$this->EsServers($compress,$resume_info);
        

        //====================================通知正排服务=====================================================
        //$this->CVFwdIndex($compress,$resume_algorithm,$resume_info['work_experience']);

        //存储redis数据
        if(!empty($redis_data)){
           $redis_data['updated_at']=date('Y-m-d H:i:s');
           $this->redis->hMset($resume_id,$redis_data);
        }

        // //存储mysql数据
        if(!empty($save_data)){
            $this->save($compress,$resume_info,$save_data);
        }

        
    }

    /**
     * 存储数据
     * @return [type] [description]
     */
    private function save($compress,$resumes,$algorithms){
        $resume_id = $compress['basic']['id'];
        
        if($this->is_update_resume_extra){
            $compress = addslashes(gzcompress(json_encode($compress)));
            $this->write_db->query("update resumes_extras set compress='$compress' where id=$resume_id"); //更新扩展表数据
            $this->is_update_resume_extra = false;
            $this->cache($resume_id,"resumes/Model_resume_extra");
        }
        
        if($this->is_update_resume){
            $resume = '';
            foreach($resumes as $key=>$value){
                $resume .= "`$key`='".addslashes($value)."',";
            }

            $resume = rtrim($resume,',');
            $sql = "update resumes set $resume where id=$resume_id";
            $this->is_update_resume = false;
            $this->write_db->query("update resumes set $resume where id=$resume_id"); //更新简历主表
        }
        

        $algorithm = '';
        foreach($algorithms as $key=>$value){
            $algorithm .= "'$key','".addslashes($value)."',";
        }
        $algorithm = rtrim($algorithm,',');
        if(empty($algorithm)) return;
        $time = date("Y-m-d H:i:s");
        $sql = "update algorithms set data=column_add(data,$algorithm),updated_at='$time' where id=$resume_id";

        $res = $this->write_db->query($sql);
        
        //$msg = empty($res) ? $resume_id." error: {$this->write_db->error}\n" : $resume_id." success\n";
        //error_log(date('Y-m-d H:i:s')."\t".$msg,3,'/opt/log/cv_tag_ids.'.date('Y-m-d'));
        //error_log(date("Y-m-d H:i:s")."\t".$sql."\n",3,'/opt/log/cv_tag_sql.'.date('Y-m-d'));
    }

    

    //=================================================================================================================================
    private function cv_tag($compress){
        if(empty($compress['work'])) return '';
        $work_list = array();
        foreach ($compress['work'] as $work){
            $work_id = $work['id'];
            if (empty($work['position_name']) && empty($work['responsibilities'])) continue;
            $work_list[$work_id] = array(
                'id'    => $work_id,
                'type'  => 0,
                'title' => empty($work['position_name']) ? '' : $work['position_name'],
                'desc'  => empty($work['responsibilities']) ? '' : $work['responsibilities']
            );
        }

        if(empty($work_list)) return '';

        $success = @$this->tag_predict->ping('data testing');
        if (!$success) {
            $this->apiinit("tag_predict");
        }

        $param=array(
            'header'=>$this->header(),
            'request'=>array(
                'c' => 'cv_tag',
                'm' => 'get_cv_tags',
                'p' => array(
                    'cv_id' => $compress['basic']['id'],
                    'work_map' => $work_list
                )
            )
        );
        $this->access("tag_predict",$param);
        $start_time = microtime(true);
        $return = $this->tag_predict->doNormal("tag_predict",json_encode($param));
        $time = (microtime(TRUE)-$start_time)*1000;
        $rs = msgpack_unpack($return);
        $this->access("tag_predict",$rs,"RSQ:",$time);
        
        if(isset($rs['response']['results'])){
            return empty($rs['response']['results']) ? '' : json_encode($rs['response']['results'],JSON_UNESCAPED_UNICODE);
        }else{
            error_log($idss,3,"/opt/log/cv_tag_timeout_ids");
            return null;
        }
    }


    public function cv_feature($compress){
        if (empty($compress['work']) && empty($compress['project'])) {
            return '';
        }

        $param=array(
            'header'=>$this->header(),
            'request'=>array(
                'c' => 'rs_feature',
                'm' => 'get_all_feature',
                'p' => array(
                    'cv_id' => $compress['basic']['id'],
                    'cv_json' => json_encode($compress)
                )
            )
        );

        $success = @$this->rs_feature_svr_online_new_format->ping('data testing');
        if (!$success) {
            $this->apiinit("rs_feature_svr_online_new_format");
        }

        $this->access("rs_feature_svr_online_new_format",$param);
        $start_time = microtime(true);
        $return = $this->rs_feature_svr_online_new_format->doNormal("rs_feature_svr_online_new_format",json_encode($param));
        $time = (microtime(TRUE)-$start_time)*1000;
        $rs = msgpack_unpack($return);
        $this->access("rs_feature_svr_online_new_format",$rs,"RSQ:",$time);
        unset($param,$return,$success,$compress);
        if(isset($rs['response']['results'])){
            return empty($rs['response']['results']) ? '' : json_encode($rs['response']['results'],JSON_UNESCAPED_UNICODE);
        }else{
            return null;
        }
    }



    /**
     * 初始化接口连接
     * @param  [type] $api [description]
     * @return [type]      [description]
     */
    private function apiinit($api){
        $this->$api = new \GearmanClient();
        $config_text = json_decode(file_get_contents("/opt/wwwroot/conf/gm.conf"),true);
        if (isset($config_text[$api]) && $config_text[$api]['host']){
            foreach ($config_text[$api]['host'] as $host){
                $this->$api->addServers($host);
            }
        }
    }
    
    /**
     * [header description]
     * @return [type] [description]
     */
    private function header(){
        return array(
            'product_name'  =>  $param['product_name'] ?? 'BIService',
            'uid'           =>  $param['uid'] ?? '9',
            'session_id'    =>  $param['session_id'] ?? '0',
            'uname'         =>  $param['uname'] ?? 'BIServer',
            'version'       =>  $param['version'] ?? '0.1',
            'signid'        =>  $param['signid'] ?? 0,
            'provider'      =>  $param['provider'] ?? 'icdc',
            'ip'            =>  $param['ip'] ?? '0.0.0.0',
            'user_ip'       =>  $param['user_ip'] ?? '0.0.0.0',
            'local_ip'      =>  $param['local_ip'] ?? '0.0.0.0',
            'log_id'        =>  $param['log_id'] ?? uniqid('bi_'),
            'appid'         =>  $param['appid'] ?? 999,
        );
    }

    private function access($api,$msg,$type="RSP:",$time=''){
        error_log(date('Y-m-d H:i:s')."\t{$this->swoole->worker_id}\t$type\t".json_encode($msg,JSON_UNESCAPED_UNICODE)."\t$time\n",3,"/opt/log/{$api}_access.".date('Y-m-d'));
    }

    public function __destruct(){
        $this->read_db->close();
        $this->write_db->close();
    }
}