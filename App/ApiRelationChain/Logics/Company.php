<?php
/**
 * 业务逻辑：
 * 1、根据公司id判断是否已经开通过，如果开通退出，否则将要开通的公司id进行存储并继续执行
 * 2、根据公司id查询gp库，将数据组装成以简历id为键名的三维数组
 * 3、遍历三维数组查询简历id是否已经存在于redis当中，如果存在取出柔和成新的数组并放回缓存，否则直接放进缓存
 * 4、同3逻辑，将数据放入mysql
 */
namespace Swoole\App\ApiRelationChain\Logics;
use Swoole\Core\AppServer;
use Swoole\Core\Log;
use Swoole\Core\Lib\Cache\Redis;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Worker;
class Company{

    public $local_db;
    private $user_compan_key= 'api_relation_uc_';
    private $company_resume_key='api_relation_cr_';
    private $company_company_key = 'api_relation_cc_';
    private $redis;
    private $limit=60;   //人脉关系数据60条
    private $es_servers;
    private $start_time;
    private $company_total = 0;
    private $resume_total = 0;
    private $type = 0;  //0 开通公司  1 刷库

    public function __construct(){
        $this->local_db = AppServer::db('bi');
        $this->gsys_db = AppServer::db('gsystem');
        $config = AppServer::$config->get('db[redis]');
        $this->redis = new Redis($config);
        $this->es_servers = new Worker('es_servers');
        $this->start_time = date('Y-m-d H:i:s');
    }

    /** 开通公司
     * @param $arr
     */
    public function index($arr){
        Log::write_log("company {$arr['company_id']} start find father...");
        $company_id = $this->father($arr['company_id']);
        Log::write_log("father company_id $company_id ......");
        $this->check($company_id,$arr['user_id']);
        Log::write_log("start read gp....");
        $res = $this->select_gp($company_id);
        if($res){
            $this->local_db->query("update relation_chain_company set status=1 where company_id=$company_id");
        }
        $this->add_indexes($company_id);
        Log::write_log("{$company_id} success....");
        $this->company_total = 1;
        $this->logger();
    }

    /**获取父公司id
     * @param $id
     * @return mixed
     */
    public function father($id){
        $cache_key = $this->company_company_key.$id;
        $cache_value = $this->redis->get($cache_key);
        Log::write_log("father id cache_value $cache_value");
        if(empty($cache_value)){
            $result = $this->gsys_db->query("select parent_id from corporations where id=$id")->fetch();
            if(isset($result['parent_id']) && $result['parent_id'] > 0 && $id != $result['parent_id']){
                return $this->father($result['parent_id']);
            }else{
                $this->redis->set($cache_key,$id);
                return $id;
            }
        }
        return $cache_value;
    }

    /** 检查公司是否已经开通
     * @param int $company_id
     * @return bool
     */
    public function check($company_id,$user_arr){
        $values='';
        $user_arr = is_array($user_arr) ? $user_arr : array($user_arr);
        foreach($user_arr as $u){
            $values .= "('$company_id','$u'),";
            $cache_value = $this->redis->get($this->user_compan_key.$u);
            if($cache_value) $this->redis->delete($this->user_compan_key.$u);
            $this->redis->set($this->user_compan_key.$u,$company_id);
        }
        $values = rtrim($values,',');
        if($values) $this->local_db->query("REPLACE into relation_chain_company (`company_id`,`user_id`) values $values");
    }

    /**
     * 每日更新触发入口 批量刷新pgsql数据
     */
    public function update(){
        Log::write_log("update db and cache start ...");
        $result = $this->local_db->query("select distinct company_id,`status` from relation_chain_company")->fetchall();
        if(empty($result)) Log::write_log("updated 0 ....");
        $this->local_db->query("TRUNCATE TABLE relation_chain");
        $this->redis->clear();
        $this->del_indexes();
        $this->company_total = count($result);
        $this->type = 1;
        Log::write_log($this->company_total." company will be update...");
        foreach($result as $row){
            $res2 = $this->local_db->query("select user_id from relation_chain_company where company_id=".$row['company_id'])->fetchall();
            foreach($res2 as $r){
                $this->redis->set($this->user_compan_key.$r['user_id'],$row['company_id']);
            }
            Log::write_log($row['company_id']." start update...");
            $res = $this->select_gp($row['company_id']);
            Log::write_log("%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%");
            if($row['status'] == 0 && $res) $this->local_db->query("update relation_chain_company set status=1 where company_id={$row['company_id']}");
        }
        Log::write_log("update indexes start...");
        $this->add_indexes();
        Log::write_log("updated complete ...");
        $this->logger();
    }

    /**
     * 更新用户和公司对应关系
     */
    public function updateuc($company_id,$user_id){
        $user_id = is_array($user_id) ? $user_id : array($user_id);
        $company_id = $this->father($company_id);
        $this->check($company_id,$user_id);
        Log::write_log("add success....");
    }

    /** 根据企业ID查询关系链数据
     * @param string $company_id
     * @return array
     */
    private function select_gp($company_id){
        $config = AppServer::$config->get('db[bi_data]');
        $str = "host=".$config['host'];
        $str .= " port=".$config['port'];
        $str .= " dbname=".$config['name'];
        $str .= " user=".$config['user'];
        $str .= " password=".$config['passwd'];
        $str .= " options='--client_encoding=".$config['charset']."'";
        $pgdb = pg_connect($str);
        if(!$pgdb){
            Log::write_log("pgsql connect fail...");
        }

        $result = pg_query($pgdb,"SELECT case when count(1) >1 then 1 else 0 end result FROM (SELECT backendid,pg_stat_get_backend_pid(S.backendid) AS procpid,pg_stat_get_backend_activity_start(S.backendid) AS start,pg_stat_get_backend_activity(S.backendid) AS current_query FROM (SELECT pg_stat_get_backend_idset() AS backendid) AS S ) AS S WHERE current_query <> '<IDLE>' and current_query like '%relations%' and (current_query like '%create%' or current_query like '%truncate%')");
        $count = pg_fetch_row($result);
        if($count[0]){
            sleep(600);
        }

        $sql1 = "select * from (select has_contact, sort_id, resume_id, re_resume_id, company_id,re_sort_id as current_sort_id from bi_data.company_function_timex_relations where cur_company_id = $company_id UNION ALL select re_has_contact AS has_contact, re_sort_id AS sort_id, re_resume_id AS resume_id, resume_id AS re_resume_id, company_id,sort_id as current_sort_id from bi_data.company_function_timex_relations where re_cur_company_id = $company_id) as t order by random()";
        $sql2 = "select * from (select has_contact,sort_id,resume_id,re_resume_id,school_id,discipline_id,re_sort_id as current_sort_id from bi_data.school_major_timex_relations where cur_company_id = $company_id UNION ALL select re_has_contact AS has_contact, re_sort_id AS sort_id, re_resume_id AS resume_id, resume_id AS re_resume_id, school_id,discipline_id,sort_id as current_sort_id from bi_data.school_major_timex_relations where re_cur_company_id = $company_id) as t order by random()";

        $resource = pg_query($pgdb,$sql1);

        $i=0;
        $page=1;
        $values='';
        while($row = pg_fetch_array($resource,null,PGSQL_ASSOC)){
            $values = $this->data($row,$company_id,$values);
            if($i==1000){
                Log::write_log("$company_id company  ".$page * $i."  ".System::get_used_memory());
                $values = rtrim($values,',');
                $this->local_db->query("REPLACE into relation_chain (`resume_id`,`company_id`,`resume_data`) values $values");
                $values = '';
                $i=0;$page++;
            }
            $i++;
        }
        if($values){
            $values = rtrim($values,',');
            $this->local_db->query("REPLACE into relation_chain (`resume_id`,`company_id`,`resume_data`) values $values");
            $values = '';
        }
        Log::write_log("$company_id company ".$page * $i." complete ".System::get_used_memory());
        pg_free_result($resource);
        $resource = pg_query($pgdb,$sql2);
        $i=0;
        $page=1;
        while($row = pg_fetch_array($resource,null,PGSQL_ASSOC)){
            $values = $this->data($row,$company_id,$values);
            if($i==1000){
                Log::write_log("$company_id school ".$page * $i." ".System::get_used_memory());
                $values = rtrim($values,',');
                $this->local_db->query("REPLACE into relation_chain (`resume_id`,`company_id`,`resume_data`) values $values");
                $values = '';
                $i=0;$page++;
            }
            $i++;
        }
        if($values){
            $values = rtrim($values,',');
            $this->local_db->query("REPLACE into relation_chain (`resume_id`,`company_id`,`resume_data`) values $values");
        }
        Log::write_log("$company_id school ".$page * $i." complete ".System::get_used_memory());
        pg_free_result($resource);
        pg_close($pgdb);
        return true;
    }


    private function data($row,$company_id,$values){
        $resume_id = $row['re_resume_id'];
        $cache_key = $this->company_resume_key.$company_id."_".$resume_id;

        unset($row['re_resume_id']);
        $cache_value = $this->redis->get($cache_key);
        if ($cache_value) {
            $old_value = json_decode($cache_value, true);
            $new_value = array($row);
            $new_arr_hash=array(md5(json_encode($row)));
            foreach ($old_value as $old) {
                $hash = md5(json_encode($old));
                if(!in_array($hash,$new_arr_hash)){
                    $new_value[] = $old;
                    $new_arr_hash[] = $hash;
                }
            }
            unset($new_arr_hash);
            $values_arr = $new_value;
        }else{
            $values_arr = array($row);
            $this->resume_total++;
        }
        $resume_data = $this->sort($values_arr);
        $this->redis->set($cache_key,$resume_data);
        $values .= "('$resume_id','$company_id','$resume_data'),";
        return $values;
    }


    /**
     * 排序+限制条件
     */
    public function sort($data){

        $current_num = count($data);
        if($current_num <= $this->limit){
            return json_encode($data);
        }

        $arr_has_contact=[];
        $arr_has_no_contact=[];
        foreach($data as $r){
            if($r['has_contact'] == 1){
                $arr_has_contact[] = $r;
            }else{
                $arr_has_no_contact[]=$r;
            }
        }
        //都是有联系方式的数据
        $has_contact_num = count($arr_has_contact);
        if($has_contact_num > $this->limit){
            unset($data,$arr_has_no_contact);
            $school=[];
            $company=[];
            foreach($arr_has_contact as $a){
                if(isset($a['company_id'])){
                    $company[]=$a;
                }else{
                    $school[]=$a;
                }
            }
            //公司的数据优先填充，不足再用学校数据填充
            $company_num = count($company);
            if($company_num >= $this->limit){
                $slice_num = $company_num - $this->limit;
                $values = array_slice($company,$slice_num);
            }else{
                $slice_num = $this->limit - $company_num;
                $school_num = count($school);
                $slice_num = $school_num - $slice_num;
                $school = array_slice($school,$slice_num);
                $values = array_merge($school,$company);
            }
        }elseif($has_contact_num == $this->limit){
            $values = $arr_has_contact;
        }else{
            $slice_num = $this->limit - $has_contact_num;
            $school=[];
            $company=[];
            foreach($arr_has_no_contact as $a){
                if(isset($a['company_id'])){
                    $company[]=$a;
                }else{
                    $school[]=$a;
                }
            }
            $company_num = count($company);
            if($company_num >= $slice_num){
                $slice_num = $company_num - $slice_num;
                $company = array_slice($company,$slice_num);
                $values = array_merge($arr_has_contact,$company);
            }else{
                $slice_num = $slice_num - $company_num;
                $school_num = count($school);
                $slice_num = $school_num - $slice_num;
                $school = array_slice($school,$slice_num);
                $values = array_merge($arr_has_contact,$company,$school);
            }
        }
        return empty($values) ? '' : json_encode($values);
    }

    /**
     * 添加索引
     * 人脉内推-全部--联合查询后台接口
     */
    private function add_indexes2(){
        $redis = $this->redis->handler();
        $redis_keys_arr = $redis->keys($this->company_resume_key.'*');
        $i=0;
        $connections=[];
        foreach($redis_keys_arr as $key){
            $key = str_replace($this->company_resume_key,'',$key);
            $tmp_arr = explode('_',$key);

            $connections[$i]['corporation_id']=$tmp_arr[0];
            $connections[$i]['resume_id']=$tmp_arr[1];
            $connections[$i]['updated']=time();
            $i++;
            if($i>999){
                $this->es_servers->client(array('p'=>array("m"=>"tobdata", "t"=>"multi_insert", "d"=>"cvconnection", "connections" =>$connections)));
                Log::write_log("es_servers success ".System::get_used_memory());
                $connections=[];
                $i=0;
            }
        }
    }

    /** 刷公司索引
     * @param int $company_id 默认则全量
     */
    public function add_indexes($company_id=0){
        Log::write_log('start index.....');
        $page_total = ceil($this->resume_total/1000);
        $page_total = $page_total > 0 ? $page_total : 1;
        $where = $company_id > 0 ? "and company_id = $company_id" : '';
        $where1 = $company_id > 0 ? " where company_id = $company_id" : '';
        for($page=1;$page<=$page_total;$page++){
            $sql = "SELECT id,resume_id,company_id FROM relation_chain WHERE id>= (SELECT id FROM relation_chain $where1 ORDER BY id asc LIMIT ". ($page-1)*1000 .", 1) $where ORDER BY id asc LIMIT 1000";
            $result = $this->local_db->query($sql)->fetchall();
            $connections=[];
            foreach($result as $r){
                $connections[]=array(
                    'corporation_id'=>$r['company_id'],
                    'resume_id'=>$r['resume_id'],
                    'updated'=>$r['id']
                );
            }
            if($connections){
                $this->es_servers->client(array('p'=>array("m"=>"tobdata", "t"=>"multi_insert", "d"=>"cvconnection", "connections" =>$connections)));
            }
            Log::write_log("es_servers {$r['company_id']} $page/$page_total ".System::get_used_memory());
        }
        Log::write_log('add_indexes complete.....');
    }



    /**
     * 删除索引
     * 人脉内推-全部--联合查询后台接口
     */
    public function del_indexes(){
        Log::write_log("es_servers will be clear....");
        $this->es_servers->client(array(
           'p'=>array(
               "m"=>"tobmultidelete",
               "d"=>"cvconnection",
               "updated"=>time(),
           )
        ));
    }


    private function logger(){
        $time=date('Y-m-d H:i:s');
        $this->local_db->query("insert into relation_chain_log (`company_total`,`resume_total`,`type`,`status`,`start`,`end`) values ('{$this->company_total}','{$this->resume_total}','{$this->type}','1','{$this->start_time}','$time')");
    }

    public function __destruct(){
        $this->gsys_db->close();
        $this->local_db->close();
    }
}