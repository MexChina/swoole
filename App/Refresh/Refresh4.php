<?php
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
use Swoole\App\Algorithm\Api;
use \RedisCluster;
class Refresh extends \Swoole\Core\App\Controller{

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

    private $db;        //全量读取数据的db
    private $db2;        //单量读写数据的db
    private $page_size = 1000;
    private $refresh_field=array(   //要刷库的字段
        //'cv_trade',
        'cv_tag',
        //'cv_entity',
        //'cv_education',     //学历识别
        //'cv_resign',      //离职率

    );


    //刷库白名单字典
    private $algorithm_field=[
        'cv_trade','cv_title','cv_tag','cv_entity','cv_education','cv_feature','cv_workyear','cv_quality','cv_language','cv_resign','cv_source'
    ];

    private $is_update_resume = false;          //是否更新主表
    private $is_update_resume_extra = false;    //是否更新压缩包表


    public function init(){
        $this->write_db = $this->db("master_icdc_".$this->swoole->worker_id);   //写
        Log::write_log("主库连接成功...");
        $this->read_db = $this->db("slave_icdc_".$this->swoole->worker_id);     //读
        Log::write_log("从库连接成功...");
        if(in_array('cv_resign',$this->refresh_field)){
            $this->bi_data = $this->db("bi_data");
        }
        

        $this->redis = new RedisCluster(NULL,$this->redis_config['pro']);
        Log::write_log("redis 连接成功...");
        $this->api = new Api();
    }

    
    public function index(){
        $this->init();
        $result = $this->read_db->query("select count(1) as `total` from resumes where is_deleted='N'")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh.......");

        for($page=1;$page<=$page_total;$page++){
            $start_time = number_format(microtime(true), 8, '.', '');

            $resume_ids=[];
            $result = $this->read_db->query("SELECT id FROM `resumes` WHERE id >= (SELECT id FROM `resumes` where is_deleted='N' ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) and is_deleted='N' ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                $resume_ids[]=$r['id'];
            }
            if (empty($resume_ids)) continue;

            $ids = implode(',',$resume_ids);
            $extras = $this->read_db->query("select * from resumes_extras where id in($ids)")->fetchall();
            foreach($extras as $extra){
                $this->algorithm($extra);
            }

            $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $qps = $this->page_size/$runtime;
            $str   = "{$runtime}s,$qps"; 
            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total},$str");
        }

        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成");
    }

    /**
     * 
     * @param  [type] $info [description]  array(id,compress)
     * @return [type]       [description]
     */
    private function algorithm($extra){
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

        if(empty($compress['basic']['id'])){
            $compress['basic']['id'] = $resume_id;
            $this->is_update_resume_extra = true;
        }

        //查询简历基本信息  es服务需要
        $resume_info = $this->read_db->query("select work_experience,resume_updated_at,updated_at,name,is_deleted from resumes where id=$resume_id")->fetch();

        
        //查询简历算法信息  正排服务和离职率需要
        $get_str='';
        foreach($this->algorithm_field as $s_field){
            $get_str.="column_get(data,'$s_field' as char) as $s_field,";
        }
        $get_str = rtrim($get_str,',');
        $resume_algorithm = $this->read_db->query("select $get_str from algorithms where id=$resume_id")->fetch();

        /*****************************************************************************************************
         *刷教育信息
         *****************************************************************************************************/
        if(in_array('cv_education',$this->refresh_field)){
            $education = $this->api->cv_education($compress);

            //更新算法表数据
            $redis_data['cv_education'] = $resume_algorithm['cv_education'] = $save_data['cv_education'] = $education['cv_education'];
            $redis_data['cv_degree'] = $resume_algorithm['cv_degree'] = $save_data['cv_degree'] = $education['cv_degree'];
            
            //更新压缩包数据
            if($compress['basic']['degree'] != $education['cv_degree']){
                $compress['basic']['degree'] = $education['cv_degree'];
                $this->is_update_resume_extra = true;
            }
        }

        /***********************************************************************************************************
         * 离职率
         ***********************************************************************************************************/
        if(in_array('cv_resign',$this->refresh_field)){
            $deliver = $this->bi_data->query("select days7_update_num,days7_deliver_num from bi_update_deliver_num where resume_id=$resume_id")->fetch();
            if(empty($deliver)){
                $deliver['days7_update_num']=0;
                $deliver['days7_deliver_num']=0;
            }
            $history = empty($resume_algorithm['cv_resign']['history']) ? '' : $resume_algorithm['cv_resign']['history'];
            $resign = $this->api->cv_resign($compress,$deliver,$history);
            $redis_data['cv_resign'] = $resume_algorithm['cv_resign'] = $save_data['cv_resign'] = $resign;
        }

        /***********************************************************************************************************
         * 职能
         ***********************************************************************************************************/
        if(in_array('cv_tag',$this->refresh_field)){
            $redis_data['cv_tag'] = $resume_algorithm['cv_tag'] = $save_data['cv_tag'] = $this->api->cv_tag($compress);
            error_log(date("Y-m-d H:i:s")."\t$resume_id\n",3,"/opt/log/cv_tag.ids");
        }

        //====================================通知es服务=======================================================
        $this->api->EsServers($compress,$resume_info);
        

        //====================================通知正排服务=====================================================
        $this->api->CVFwdIndex($compress,$resume_algorithm,$resume_info['work_experience']);

        //存储redis数据
        if(!empty($redis_data)){
            $redis_data['updated_at']=date('Y-m-d H:i:s');
            $this->redis->hMset($resume_id,$redis_data);
        }

        //存储mysql数据
        if(!empty($save_data)) $this->save($compress,$resume_info,$save_data);
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
            Log::writelog($sql);
            $this->write_db->query("update resumes set $resume where id=$resume_id"); //更新简历主表
        }
        

        $algorithm = '';
        foreach($algorithms as $key=>$value){
            $algorithm .= "'$key','".addslashes($value)."',";
        }
        $algorithm = rtrim($algorithm,',');
        if(empty($algorithm)) return;
        $time = date("Y-m-d H:i:s");
        $this->write_db->query("update algorithms set data=column_add(data,$algorithm),updated_at='$time' where id=$resume_id");
        $this->api->cache($resume_id,"resumes/Model_resume_algorithm");
    }

    public function icdc_basic($ids){
        $this->icdc_basic->client(array(
            "c"=>"Logic_refresh",
            "m"=>"brushes",
            "p"=>array(
                "resume_id"=>$ids,
                "field"=>['cv_tag'],
                "refresh_time"=>"1"                    //是否更新 resume_flag 时间  布尔  1 | 0
        )),true,true);
    }

    //=================================================================================================================================
    public function __destruct(){
        $this->read_db->close();
        $this->write_db->close();
        if(in_array('cv_resign',$this->refresh_field)){
            $this->bi_data->close();
        }
    }
}