<?php
/**
 * 业务逻辑：
 * 1、根据公司id判断是否已经开通过，如果开通退出，否则将要开通的公司id进行存储并继续执行
 * 2、根据公司id查询gp库，将数据组装成以简历id为键名的三维数组
 * 3、遍历三维数组查询简历id是否已经存在于redis当中，如果存在取出柔和成新的数组并放回缓存，否则直接放进缓存
 * 4、同3逻辑，将数据放入mysql
 */
namespace Swoole\App\Relation\Logics;
use Swoole\Core\AppServer;
use Swoole\Core\Log;
use Swoole\Core\Lib\Cache\Redis;
class Company{

    public $local_db;
    private $user_company_key= 'api_relation_uc_';
    private $company_resume_key='api_relation_cr_';
    private $company_company_key = 'api_relation_cc_';
    private $redis;
    private $limit=60;   //人脉关系数据60条
    private $start_time;
    private $company_total = 0;
    private $resume_total = 0;
    private $type = 0;  //0 开通公司  1 刷库

    public function __construct(){
        $this->local_db = AppServer::db('bi');
        $this->bi_data = AppServer::db('bi_data');
        $config = AppServer::$config->get('db[redis]');
        $this->redis = new Redis($config);
        $this->start_time = date('Y-m-d H:i:s');
    }

    /** 开通公司
     * 1、根据公司id查找顶级公司id
     * 2、检查该公司是否已经开通 未开通则将数据写入到 company表记录中
     * 3、从db中将该公司的数据写入到db中
     * 4、在日志表中加入开通的一条记录
     * ===================================20161214=============
     * 1、查询表中是否存在 company_id+user_id 没有添加有不操作
     *
     * 第一次开通  公司A+(张三，张四，张五，张六）
     * 第二次开通  公司A+（张三，张七）
     *
     * 最终结果 公司A+(张3,4,5,6,7)
     * @param $arr
     */
    public function index($arr){
        $user_ids = is_array($arr['user_id']) ? $arr['user_id'] : array();
        $company_id = $arr['company_id'];

        foreach($user_ids as $user_id){
            $res = $this->local_db->query("select * from relation_chain_company where company_id='{$company_id}' and user_id='$user_id'")->fetch();
            if(empty($res)) $this->local_db->query("insert into relation_chain_company(`company_id`,`user_id`,`status`) VALUES ($company_id,$user_id,1)");
        }
    }

    /** 获取公司id
     * ========================20161214========================
     * 1、根据用户id获取开通时的公司id
     * 2、根据开通时的id获取顶级父公司
     * @param $user_id
     * @return int|mixed
     */
    public function uid2cid($user_id){
        $company_id = $this->redis->get($this->user_company_key.$user_id);
        $company_id = (int)$company_id;
        if($company_id == 0){
            $res = $this->local_db->query("select company_id from relation_chain_company where user_id=$user_id and status=1")->fetch();
            $company_id = $this->father($res['company_id']);
            if($company_id) $this->redis->set($this->user_company_key.$user_id,$company_id);
        }
        return $company_id;
    }

    /** 根据公司id获取顶级父公司id
     * 1、先验证是否开通
     * 2、再查父公司id
     * @param $company_id
     * @return int|mixed
     */
    public function local_cid($company_id){
        $cache_key = $this->company_company_key.$company_id;
        $cache_value = $this->redis->get($cache_key);
        if(empty($cache_value)){
            $res = $this->local_db->query("select * from relation_chain_company where company_id=$company_id")->fetch();
            if(empty($res)) return 0;
        }
        return $this->father($company_id);
    }

    /**
     * 每日更新触发入口 更新内容：
     * 1、先将 company_mapping 复制更新
     * 2、获取已经开通的公司 用户id和公司id对应关系
     *
     *
     */
    public function update(){
        $parent_arr=[];
        /**
         * 1、复制父子公司映射表
         */
        Log::write_log("start to copy company_mapping ...");
        $gsys_db = AppServer::db('bi_gsystem');
        $result = $gsys_db->query("select * from company_smapping")->fetchall();
        $this->local_db->query("TRUNCATE TABLE company_mapping");
        foreach($result as $r){
            $this->local_db->query("insert into company_mapping(`company_id`,`pcompany_id`,`depth`) VALUES ({$r['company_id']},{$r['pcompany_id']},{$r['depth']})");
        }
        $gsys_db->close();

        /********************************************************
         * 2、清空redis所有数据
         */
        $this->redis->clear();

        /********************************************************
         * 3、更新用户==顶级父公司对应关系
         */
        Log::write_log("start to update user_company mapping ...");
        $result = $this->local_db->query("select user_id,company_id from relation_chain_company")->fetchall();
        foreach($result as $row){
            $parent_company_id = $this->father($row['company_id']);
            $parent_arr[]=$parent_company_id;
            $this->redis->set($this->user_company_key.$row['user_id'],$parent_company_id);
        }

        /**********************************************************
         * 4、更新人脉==伯乐信息对应关系
         */
        $parent_arr = array_unique($parent_arr);
        $this->company_total = count($parent_arr);
        $this->type = 1;
        Log::write_log($this->company_total." company will be update...");
        foreach ($parent_arr as $pid){
            $this->select($pid);
        }
        Log::write_log("updated complete ...");
        $this->logger();
    }


    /** 解析数据
     * @param $parent_id int 顶级公司id
     * @param int $resume_id int 简历id
     * @return bool
     *
     * 1、若简历id为空，则执行将传入的公司id下的所有数据更新到缓存中
     * 2、若简历id不为空，则将传入的公司下面的这个简历的数据更新到缓存中
     * 3、返回的true和false是给 通过简历id+公司id获取的时候，在缓存中未命中然后读db，判断db是否命中的依据
     */
    public function select($parent_id,$resume_id=0){
        $i=$j=false;
        $where = $resume_id > 0 ? "re_cur_parent_company_id=$parent_id and resume_id=$resume_id" : "re_cur_parent_company_id=$parent_id";
        $colleague = $this->bi_data->query("select * from colleague_relations where $where")->fetchall();
        if(empty($colleague)){
            $i=true;
        }else{
            foreach($colleague as $c){
                $this->parse_data($c,$parent_id);
            }
        }

        $schoolmate = $this->bi_data->query("select * from schoolmate_relations where $where")->fetchall();
        if(empty($schoolmate)){
            $j=true;
        }else{
            foreach($schoolmate as $c){
                $this->parse_data($c,$parent_id);
            }
        }
        return $i == true && $j == true ? false : true;
    }

    /**
     * @param $row[
     *      'resume_id'                 //人脉简历id
     *      're_resume_id'              //伯乐简历id
     *      'company_id'                //同事时公司id
     *      're_cur_company_id'         //伯乐现在公司id
     *      're_cur_parent_company_id'  //伯乐现在公司父id
     *      're_sort_id'                //同事关系时伯乐的工作经历段id
     *      'sort_id'                   //人脉工作经历id
     *      'current_sort_id'           //人脉简历和伯乐同事时人脉的工作经历段id
     *      're_has_contact'            //伯乐是否有联系方式
     *      'work_month'                //共事的天数
     *      're_type'                   //伯乐类型：0=无，1=潜力伯乐，2=认证伯乐，3=认证潜力伯乐
     *      'school_id'                 //同学时学校id
     *      'discipline_id'             //同学时专业id
     * ]
     *
     * key = 人脉简历id+伯乐公司父id
     *
     * @param $company_id
     * has_contact, sort_id, resume_id, re_resume_id, company_id,re_sort_id as current_sort_id
     * has_contact,sort_id,resume_id,re_resume_id,school_id,discipline_id,re_sort_id as current_sort_id
     */
    private function parse_data($row,$company_id){
        $resume_id = $row['resume_id'];
        $cache_key = $this->company_resume_key.$company_id."_".$resume_id;

        $new_arr['resume_id'] = $row['re_resume_id'];
        if(isset($row['company_id'])) $new_arr['company_id'] = $row['company_id'];
        $new_arr['sort_id'] = $row['re_sort_id'];
        $new_arr['current_sort_id'] = $row['sort_id'];
        $new_arr['has_contact'] = $row['re_has_contact'];
        if(isset($row['work_month'])) $new_arr['work_month'] = $row['work_month'];
        if(isset($row['re_type'])) $new_arr['re_type'] = $row['re_type'];
        if(isset($row['school_id'])) $new_arr['school_id'] = $row['school_id'];
        if(isset($row['discipline_id'])) $new_arr['discipline_id'] = $row['discipline_id'];

        $cache_value = $this->redis->get($cache_key);
        if ($cache_value) {
            $cache_value_arr = json_decode($cache_value, true);
            $flag=true;
            foreach ($cache_value_arr as $old) {
                $diff = array_diff($new_arr,$old);
                if(empty($diff)){
                    $flag = false;break;
                }
            }
            if($flag) $cache_value_arr[]=$new_arr;
        }else{
            $cache_value_arr = array($new_arr);
            $this->resume_total++;
        }
        $resume_data = $this->sort($cache_value_arr);
        $this->redis->set($cache_key,$resume_data);
    }



    /**获取父公司id
     * @param $id
     * @return mixed
     */
    public function father($id){
        if(empty($id)) return 0;
        $cache_key = $this->company_company_key.$id;
        $cache_value = $this->redis->get($cache_key);
        Log::write_log("father id cache_value $cache_value");
        if(empty($cache_value)){
            $result = $this->local_db->query("select * from company_mapping where company_id=$id")->fetch();
            if(isset($result['pcompany_id']) && $result['pcompany_id'] > 0 && $id != $result['pcompany_id'] && $result['depth'] != 1){
                return $this->father($result['pcompany_id']);
            }else{
                $this->redis->set($cache_key,$id);
                return $id;
            }
        }
        return $cache_value;
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

    /**更新未命中的数据
     * @param $param
     * @return bool
     */
    public function find($param){
        $company_id = $this->uid2cid($param['user_id']);
        Log::write_log("async uid:{$param['user_id']} company_id:$company_id  resume_id:{$param['resume_id']}");
        return $this->select($company_id,$param['resume_id']);
    }

    private function logger(){
        $time=date('Y-m-d H:i:s');
        $this->local_db->query("insert into relation_chain_log (`company_total`,`resume_total`,`type`,`status`,`start`,`end`) values ('{$this->company_total}','{$this->resume_total}','{$this->type}','1','{$this->start_time}','$time')");
    }

    public function __destruct(){
        $this->local_db->close();
        $this->bi_data->close();
    }
}