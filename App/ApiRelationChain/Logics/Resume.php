<?php
namespace Swoole\App\ApiRelationChain\Logics;
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
        $this->mysql = AppServer::db('bi');
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
        return $results;
    }

    /** 根据公司id获取内推简历的列表
     * @param $company_id int 公司id
     * @param $page int 当前分页
     * @param $page_size int 每页显示的条数
     * @return array|mixed 列表数据
     */
    public function search($company_id,$page,$page_size){
        $parent_id = $this->redis->get($this->company_company_key.$company_id);
        if(empty($parent_id)){
            $company_obj = new Company();
            $parent_id = $company_obj->father($company_id);
        }
        $cache_key = $this->company_cc_list_key.$parent_id.'_'.$page.'_'.$page_size;
        $cache_value = $this->redis->get($cache_key);
        $cache_value = json_decode($cache_value,true);
        if(empty($cache_value) || empty($cache_value['total'])){
            $results = $this->mysql->query("select count(1) as `count` from relation_chain where company_id=$parent_id")->fetch();
            $new_arr=[];
            $page_end = ceil($results['count']/$page_size);
            $page = $page > 0 && $page <= $page_end ? $page : 1;
            $sql = "SELECT resume_id,resume_data FROM relation_chain WHERE id>= (SELECT id FROM relation_chain where company_id='$parent_id' ORDER BY id asc LIMIT ".($page-1)*$page_size.", 1) and company_id = '$parent_id' ORDER BY id asc LIMIT $page_size";
            $result = $this->mysql->query($sql)->fetchall();
            foreach($result as $row){
                $new_arr[$row['resume_id']]=json_decode($row['resume_data'],true);
            }
            if(!empty($new_arr)){
                $new_arr['total'] = $results['count'];
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