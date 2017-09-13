<?php
namespace Swoole\App\Gsystem\Controller;

use Swoole\Core\Log;
class Logo{
    private $redis;
    private $mysql;
    public function __construct($obj){
        $this->redis = $obj['redis'];
        $this->mysql = $obj['mysql'];
        $this->crop_tag = $obj['crop_tag'];
    }

    public function save($params){
        //是否传递id  如果传递则直接操作，否则去取id
        $type = (int)$params['type'];
        $src_no = (int)$params['src_no'];
        $this->table = $type == 1 ? 'schools_logos' : 'corporations_logos';
        $this->field = $type == 1 ? 'school_id' : 'corporation_id';

        $values = '';
        foreach($params['data'] as $d){

            //id如果不存在则去接口识别，如果识别不了则抛弃这条数据
            if(empty($d['id'])) $d['id'] = $this->get_id($d['name'],$type);
            $id = (int)$d['id'];
            if($id === 0) continue;

            $cache_key = "logo_{$type}_{$id}_{$src_no}";

            //查缓存是否存在，如果缓存存在，则抛弃这条数据
            $cache = $this->redis->get($cache_key);
            if($cache){
                Log::write_log("$id redis 已经存在！");
                continue; //缓存存在
            }
            $resource = $this->mysql->query("select logo from {$this->table} where {$this->field}={$id} and type_id={$src_no} limit 1");
            $res = $resource->fetchall();
            if(count($res) == 1){
                Log::write_log("$id redis 已经存在！");
                $this->redis->set($cache_key,$res[0]['logo']);
                continue;
            }


            //保存缓存数据
            unset($d['name']);
            $this->redis->set($cache_key,$d['logo']);

            //保存到数据库
            $values .= "('".$src_no."','$id','{$d['logo']}'),";
        }
        $values = rtrim($values,',');
        if($values){
            $this->mysql->query("insert into `{$this->table}` (`type_id`,`{$this->field}`,`logo`) VALUES $values");
            Log::write_log("保存数据库成功！");
        }
        return array('err_no'=>0,'err_msg'=>'','results'=>"操作成功");
    }


    /**查询公司logo或学校logo
     * @param $param
     * @return array
     */
    public function get($param){
        $flag = is_array($param['id']) ? true : false;
        $type = (int)$param['type'];
        $src_no = (int)$param['src_no'];
        if($flag == false){
            $id = (int)$param['id'];
            $cache_key = "logo_{$type}_{$id}_{$src_no}";
            $cache_value = $this->redis->get($cache_key);
            $results = empty($cache_value) ? $this->select($id,$type,$src_no) : $cache_value;
        }else{
            $cache_key = array();
            foreach($param['id'] as $k=>$id){
                $cache_key[$k] = "logo_{$type}_{$id}_{$src_no}";
            }
            $redis_handler = $this->redis->handler();
            $cache_value = $redis_handler->MGET($cache_key);
            $results = array_combine($param['id'],$cache_value);
            foreach($results as $id=>$cache_value){
                $results[$id] = empty($cache_value) ? $this->select($id,$type,$src_no) : $cache_value;
            }
        }
        return array('err_no'=>0,'err_msg'=>'','results'=>$results);
    }

    /** 缓存中获取不到需要取数据库的
     * @param $id integer 要获取的id   公司id或者学校id
     * @param $type integer  分类 公司还是学校
     * @param $src_no integer 来源 上传还是抓取
     * @param bool $is_json 是否返回为json格式
     * @return mixed|null|string|array
     */
    private function select($id,$type,$src_no){
        $cache_key = "logo_{$type}_{$id}_{$src_no}";
        $table = $type == 0 ? 'corporations_logos' : 'schools_logos';
        $wid = $type==0 ? 'corporation_id' : 'school_id';
        $res = $this->mysql->query("select logo from $table where $wid = $id");
        $result = $res->fetchall();
        if(empty($result)){
            $cache_value = null;
            Log::write_log("$id logo在库中不存在...");
        }else{

            /**
             *  src_no      type_id     src_no_value
             *  0           0           0   如果有 0 的值直接取
             *  0           1           0   如果没有 1 的值取 0 的值
             *  1           0           1   如果没有 0 的值取 1 的值
             *  1           1           1   如果有 1 的值直接取
             */
            foreach($result as $r){
                if($r['type_id'] == $src_no){  //  0 或 1 都有值的时候取
                    $cache_value = $r['logo'];
                    break;
                }
            }
            //当获取不到和src_no对应的值，那么就取隔壁的值
            if(empty($cache_value)){
                $cache_value = $result[0]['logo'];
            }
            $this->redis->set($cache_key,$cache_value);
        }
        Log::write_log("$id 重新读库刷新缓存...");
        return $cache_value;
    }

    /**
     * @param $name  公司名  学校名
     * @param $type  0 为公司名  1 为学校名
     * @return mixed
     */
    private function get_id($name,$type){
        $this->crop_tag->set_header(array(
            'uid' => 1,
            'uname' => 'dongqing.shi',
            'version' => 1,
            'signid' => 1,
            'provider' => 'apilogo',
            'ip' => "192.168.8.43"
        ));

        if($type == 0){
            $request = array(
                'cv_id' => '',
                'work_list' => [array(
                    'position' => '',
                    'company_name' => $name,
                    'work_id' => 0,
                    'desc' => '',
                    'industry_name' => ''
                )]
            );
        }else{
            $request = array(
                'c' => 'CVEducation',
                'm' => 'query',
                'p' => [array(
                    'school' => $name,
                    'major' => '',
                    'degree' => '',
                )],
            );
        }

        $field = $type == 0 ? 'company_id' : 'school_id';
        $response = $this->crop_tag->client($request,true);
        $id = (int)$response['result'][0][$field];
        Log::write_log("$name === $id ......");
        return $id;
    }
}