<?php
namespace Swoole\App\Relation\Logics;
use Swoole\Core\AppServer;
use Swoole\Core\Lib\Cache\Redis;
class Resume{

    public $redis;
    public $mysql;
    private $user_compan_key= 'api_relation_uc_';
    private $company_resume_key='api_relation_cr_';
    private $company_company_key = 'api_relation_cc_';
    private $company_cc_list_key = 'cl_';
    public function __construct(){
        $config = AppServer::$config->get('db[redis]');
        $this->redis = new Redis($config);
        $this->mysql = AppServer::db('bi_data');
    }


    /** 多条同时读取，前提是先存在于redis中
     * @param $resume_ids
     * @return array
     */
    public function mget($request){
        $keys=[];
        $company_id = $this->redis->get($this->user_compan_key.$request['user_id']);
        $company_id = (int)$company_id;

        $request['resume_id'] = is_array($request['resume_id']) ? $request['resume_id'] : array($request['id']);
        foreach($request['resume_id'] as $k=>$r){
            $keys[$k] = $this->company_resume_key.$company_id.'_'.$r;
        }

        $redis_handler = $this->redis->handler();
        $result = $redis_handler->MGET($keys);
        $results = array_combine($request['resume_id'],$result);

        $company = new Company();
        $new_results=[];
        foreach($results as $k=>$r){
            $value = json_decode($r,true);
            if(empty($value)){
                $bloolen = $company->find(array('user_id'=>$request['user_id'],'resume_id'=>$k));
                if($bloolen){
                    $one_cache = $this->redis->get($this->company_resume_key.$company_id."_".$k);
                    $new_results[$k] = json_decode($one_cache,true);
                }else{
                    $new_results[$k]= array();
                }
            }else{
                $new_results[$k]=$value;
            }
        }

        return $new_results;
    }


    /** 根据公司id获取内推简历的列表
     * @param $company_id  int 公司id
     * @param $page int 当前分页
     * @param $page_size int 每页显示的条数
     * @param $type int 分类id 0=学校 1=公司
     * @return array list
     */
    public function search($company_id,$page,$page_size,$type){
        $parent_id = $this->redis->get($this->company_company_key.$company_id);
        if(empty($parent_id)){
            $company_obj = new Company();
            $parent_id = $company_obj->father($company_id);
        }
        $cache_key = $this->company_cc_list_key.$parent_id.'_'.$page.'_'.$page_size.'_'.$type;
        $cache_value = $this->redis->get($cache_key);
        $cache_value = json_decode($cache_value,true);

        $table = $type == 1 ? 'colleague_relations' : 'schoolmate_relations';
        if(empty($cache_value) || empty($cache_value['total'])){
            $results = $this->mysql->query("select count(1) from $table where re_cur_parent_company_id=$parent_id")->fetch();
            $new_arr=[];
            $page_end = ceil($results['COUNT0']/$page_size);
            $page = $page > 0 && $page <= $page_end ? $page : 1;
            $sql = "SELECT * FROM $table WHERE resume_id>= (SELECT resume_id FROM $table where re_cur_parent_company_id='$parent_id' ORDER BY resume_id asc LIMIT ".($page-1)*$page_size.", 1) and re_cur_parent_company_id = '$parent_id' ORDER BY resume_id asc LIMIT $page_size";
            $result = $this->mysql->query($sql)->fetchall();

            foreach($result as $k=>$row){
                $res = $this->mysql->query("select re_sort_id from $table where resume_id='{$row['re_resume_id']}' and re_resume_id='{$row['resume_id']}'")->fetch();
                $new_arr[$k]=array(
                    'resume_id'=>$row['re_resume_id'],
                    'company_id'=>$row['company_id'],
                    'sort_id'=>$row['re_sort_id'],
                    'has_contact'=>$row['re_has_contact'],
                    'current_sort_id'=>empty($res['re_sort_id']) ? 0 : $res['re_sort_id'],
                );
                if(isset($row['work_month'])) $new_arr[$k]['work_month'] = $row['work_month'];
                if(isset($row['re_type'])) $new_arr[$k]['re_type'] = $row['re_type'];
                if(isset($row['school_id'])) $new_arr[$k]['school_id'] = $row['school_id'];
                if(isset($row['discipline_id'])) $new_arr[$k]['discipline_id'] = $row['discipline_id'];
            }
            if(!empty($new_arr)){
                $new_arr['total'] = $results['COUNT0'];
                $this->redis->set($cache_key,json_encode($new_arr),3600);
            }
        }else{
            $new_arr = $cache_value;
        }
        return $new_arr;
    }

    public function __destruct(){
        $this->mysql->close();
    }
}