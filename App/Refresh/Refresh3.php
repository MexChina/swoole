<?php
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
use Swoole\App\Algorithm\Api;
use \RedisCluster;
use \SplFileObject;
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


    private $page_size = 100;
    private $refresh_field=array(   //要刷库的字段
        //'cv_trade',
        'cv_tag',
        //'cv_entity',
        //'cv_education',     //学历识别
        //'cv_resign',      //离职率

    );

    private $refresh_servers=array(
        //'es_servers',
        //'fwdindex_service_online',
        'icdc_online',
        'tag_predict' //cv_tag 的worker
    );

    //刷库白名单字典
    private $algorithm_field=[
        'cv_trade','cv_title','cv_tag','cv_entity','cv_education','cv_feature','cv_workyear','cv_quality','cv_language','cv_resign','cv_source'
    ];

    private $is_update_resume = false;          //是否更新主表
    private $is_update_resume_extra = false;    //是否更新压缩包表


    public function init(){
        $this->read_db = $this->db("slave_icdc_".$this->swoole->worker_id);     //读
        Log::write_log("从库连接成功...");
        $this->write_db = $this->db("master_icdc_".$this->swoole->worker_id);   //写
        Log::write_log("主库连接成功...");
        if(in_array('cv_resign',$this->refresh_field)){
            $this->bi_data = $this->db("bi_data");
        }

        $this->redis = new RedisCluster(NULL,$this->redis_config['pro']);
        Log::write_log("redis 连接成功...");
        foreach($this->refresh_servers as $worker_name){
            $this->apiinit($worker_name);
        }
    }

    public function one($id=11583094){
        $suffix = ($id%8) + floor($id/40000000) * 8;
        if($this->swoole->worker_id ==  $suffix){
            $extra = $this->read_db->query("select * from resumes_extras where id=$id")->fetch();
            $this->algorithm($extra);
            Log::write_log($id."\tok");
        }
    }

    public function index(){
        $this->init();
        $ids = new SplFileObject("/opt/log/function_flush_resume_id.csv");
        $i=0;
        foreach($ids as $id){
            $id = (int)$id;
            if($id > 0){
                $this->one($id);
                $i++;
            }
        }
        Log::write_log("/opt/log/function_flush_resume_id.csv 刷库完成 共$i条\tok");
    }

    

    public function indexbak(){
        $this->init();

        $result = $this->read_db->query("select max(id) as id from resumes limit 1")->fetch();    //获取最大的id
        $result2 = $this->read_db->query("select min(id) as id from resumes limit 1")->fetch();    //获取最小的id
        $start_id = $result2['id'];
        $end_id = $result['id']; 
        if(empty($result2['id']) && empty($end_id = $result['id'])){
            Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成");
            return;
        }              //
        Log::write_log("icdc_{$this->swoole->worker_id} [$start_id,$end_id] to refresh.......");
        while($start_id <= $end_id){
            $limit = $start_id + $this->page_size;
            $start_time = number_format(microtime(true), 8, '.', '');
            $resumes_extras = $this->read_db->query("select id,compress from resumes_extras where id >= $start_id and id < $limit")->fetchall();
            foreach ($resumes_extras as $r) {
                $this->algorithm($r);
            }
            $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $str   = "{$runtime}s";
            Log::write_log("icdc_{$this->swoole->worker_id},{$start_id}-{$limit},$str");
            $start_id =  $limit;
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
            $res = $this->cv_tag($compress);
            if($res === null){
                return;
            }
            $redis_data['cv_tag'] = $resume_algorithm['cv_tag'] = $save_data['cv_tag'] = $res;
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

        //存储mysql数据
        if(!empty($save_data)){
            $this->save($compress,$resume_info,$save_data);
        }
        // Log::write_log($resume_id." ok...");
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
        
        $msg = empty($res) ? $resume_id." error: {$this->write_db->error}\n" : $resume_id." success\n";
        error_log(date('Y-m-d H:i:s')."\t".$msg,3,'/opt/log/cv_tag_ids'.date('Ymd'));
        error_log(date("Y-m-d H:i:s")."\t".$sql."\n",3,'/opt/log/cv_tag_sql'.date("Ymd"));
        $this->cache($resume_id,"resumes/Model_resume_algorithm");
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
        // Log::write_log("code: ".$this->tag_predict->returnCode());
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


    /**
     * EsServers 接口实例化
     * @param [type] $compress [description]
     * @param [type] $resume   [description]
     */
    private function EsServers($compress,$resume){
        $success = @$this->es_servers->ping('data testing');
        if (!$success) {
            $this->apiinit("es_servers");
        }
        $param['header']=$this->header();
        $param['request']=array(
            'p'=>array(
                'm'                 => 'tobresume',
                't'                 => 'insert',
                'id'                => $compress['basic']['id'],
                'is_deleted'        => $resume['is_deleted'],
                'work_experience'   => (int)$resume['work_experience'],
                'resume_updated_at' => $resume['resume_updated_at'],
                'updated_at'        => $resume['updated_at'],
                'name'             => $resume['name'],
                'extra'             => json_encode($compress)
            )
        );
        $this->es_servers->doBackground("es_servers",msgpack_pack($param));
        $this->access("es_servers",$param);
        // Log::write_log($compress['basic']['id']." EsServers ok...");
    }


    /**
     * 正排接口实例化
     * @param [type] $compress    [description]
     * @param [type] $algorighm   [description]
     * @param [type] $cv_workyear [description]
     */
    private function CVFwdIndex($compress,$algorighm,$cv_workyear){
        $resume_id = (int)$compress['basic']['id'];
        if($resume_id <=0) return;

        $success = @$this->fwdindex_service_online->ping('data testing');
        if (!$success) {
            $this->apiinit("fwdindex_service_online");
        }
        $param['header']=$this->header();
        $param['request'] = array(
            'c' => 'CVFwdIndex',
            'm' => 'add',
            'p' => array(
                $resume_id => array(
                    'cv_id'           => $resume_id,
                    'cv_source'       => $algorighm['cv_source'],
                    'cv_trade'        => $algorighm['cv_trade'],
                    'cv_title'        => $algorighm['cv_title'],
                    'cv_tag'          => $algorighm['cv_tag'],
                    'cv_entity'       => $algorighm['cv_entity'],
                    'cv_education'    => $algorighm['cv_education'],
                    'cv_feature'      => $algorighm['cv_feature'],
                    'cv_degree'       => $algorighm['cv_degree'],
                    'work_experience' => $cv_workyear,
                    'cv_json'         => json_encode($compress)
                )
            )
        );

        $this->fwdindex_service_online->doBackground("fwdindex_service_online",msgpack_pack($param));
        $this->access("fwdindex_service_online",$param);
        // Log::write_log($resume_id." CVFwdIndex ok...");
    }

    /**
     * 更新icdc的缓存
     * @param  [type] $id    [description]
     * @param  [type] $model [description]
     * @return [type]        [description]
     */
    private function cache($id,$model){
        $success = @$this->icdc_online->ping('data testing');
        if (!$success) {
            $this->apiinit("icdc_online");
        }
        $param['header']=$this->header();
        $param['request']=array(
            "c"=>"Logic_refresh",
            "m"=>"cache",
            "p"=>array(
                "id"=>$id,
                "model"=>$model
            )
        );
        $this->icdc_online->doBackground("icdc_online",json_encode($param));
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
        Log::write_log("client $api connect success...");
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
        if(in_array('cv_resign',$this->refresh_field)){
            $this->bi_data->close();
        }
    }
}
