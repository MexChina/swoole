<?php

namespace Swoole\App\Mapping;
use Swoole\Core\Log;
use Swoole\Core\AppServer;
use Swoole\Core\Lib\Worker;
use \Exception;
use \Memcached;
/**
 * 猎头简历和tob简历建立联系
 * 员工保留简历和tob简历建立联系
 */
class Mapping extends \Swoole\Core\App\Controller
{

    private $db;
    private $cache;
    private $src_config = array(87,88,89);      //如果新增，则需要注册，将对应src数字添加到此数组中
    private $api;
    private $hunter;
    public function init()
    {
        $config = AppServer::$config->get('db[memcache]');
        $this->cache = new Memcached();
        $this->cache->setOption(Memcached::OPT_HASH,Memcached::HASH_DEFAULT);                     //设置keyhash算法
        $this->cache->setOption(Memcached::OPT_COMPRESSION,true);                                 //压缩数据
        $this->cache->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT); //设置服务器hash算法 默认采用余数分布式hash 采用一致性hash
        $this->cache->setOption(Memcached::OPT_REMOVE_FAILED_SERVERS, true);
        $this->cache->setOption(Memcached::OPT_RETRY_TIMEOUT, $config['config']['timeout']);      //设置过期时间
        $this->cache->addServers($config['server']);
    }

    /**
     * 业务初始化入口
     * @return [type] [description]
     */
    public function start()
    {
        $this->db = $this->db("icdc_map");
        $this->hunter = new Worker("toh_resumes_offline");
        if($this->swoole->worker_id == 0){
            $this->api = new Worker("cv_uniq_server_online");
            $this->unique_query();      //后台守护进程
        }else{
            $this->init();
            $this->index();             //gearman 服务
        }
    }

    /** 对外接口 
     * 根据work 设置进程 读写
     * m    get                     register
     * p    src && cv_id||src_no    src id data
     */
    public function index(){
        $worker = new Worker("icdc_map");
        $worker->worker(function($request){
            if(empty($request['m']) || empty($request['p'])){
                return array('err_no'=>1,'err_msg'=>'Error Processing Request m or p','results'=>array());
            }
            $m = strtolower($request['m']);
            try{
                switch ($m) {
                    case 'get':         //获取mapping信息
                        $results = $this->mapping_info($request['p']);
                        break;
                    case 'register':    //注册mapping信息
                        $results = $this->mapping_data($request['p']);
                        break;
                    case 'set':        //特殊情况人工直接指定
                        $results = $this->mapping_save($request['p']);
                        break;
                    case 'query':
                        $results = $this->query($request['p']);break;
                    default:
                        $results = array('err_no'=>1,'err_msg'=>'Error Processing Request m','results'=>array());
                        break;
                }
            }catch(Exception $e){
                $results = array('err_no'=>$e->getCode(),'err_msg'=>$e->getMessage(),'results'=>array());
            }
            return array('results'=>$results);
        });
    }

    /**
     * 处理其他来源的简历信息进行存储
     * @param  array  $param [description]
     * @return array        处理结果
     */
    public function mapping_data(array $param):array
    {
        if(empty($param['src'])) throw new Exception("Error Processing Request src", 1);
        if(empty($param['id'])) throw new Exception("Error Processing Request id", 1);
        if(empty($param['data'])) throw new Exception("Error Processing Request data", 1);
        $src = (int)$param['src'];
        if(!in_array($src,$this->src_config)) throw new Exception("Error Processing Request src", 1);
        $src_no = (int)$param['id'];
        $src_data = addslashes(json_encode($param['data'],JSON_UNESCAPED_UNICODE));
        try{
            $this->db->query("replace into `tob_maps_data`(`src`,`src_no`,`src_data`) values ($src,$src_no,'$src_data');");
        }catch(\PDOException $e){
            Log::writelog($e->getMessage());
        }
        return array('results'=>'success');
    }

    /**
     * 处理mapping信息
     * @return [type] [description]
     *
     * 第一个数字1 0 代表是取b端简历还是取非b端简历
     * 第二三个数字是来源编号
     * 剩下的是简历id
     * 1899834903   这个  1  取非b端简历      89  代表猎头简历   9834903  b端的简历id
     * 899834903    这个  0  取b端简历        89  代表猎头简历   9834903  非b端简历id
     */
    public function mapping_info(array $param):array
    {
        if(empty($param['src'])) throw new Exception("Error Processing Request src", 1);
        if(empty($param['ids'])) throw new Exception("Error Processing Request ids", 1);
        $src = (int)$param['src'];
        if(!in_array($src,$this->src_config)) throw new Exception("Error Processing Request src", 1);
        $type = empty($param['type']) ? "0" : "1";
        $ids = is_array($param['ids']) ? $param['ids'] : array($param['ids']);
        if(count($ids) > 100) throw new Exception("Error Processing Request num [1,100]", 1);
        if(empty($ids)) throw new Exception("Error Processing Request ids", 1);

        $key=array();
        $cache_key=array();
        foreach($ids as $id){
            $key[]=(int)($src.$id);
            $cache_key[]=(int)($type.$src.$id);
        }

        //----------先取缓存----------------
        
        $result = $this->cache->getMulti($cache_key);       //取缓存
        $keys_arr = array_keys($result);                    //将缓存中取到的key拿出
        $new_keys = array_diff($cache_key,$keys_arr);       //求出没有取到值得key

        $key_len = $type == 1 ? strlen($src)+1 : strlen($src);
        $val_len = $type == 1 ? strlen($src) : strlen($src)+1;
        //----------再取db------------------
        if(!empty($new_keys)){
            
            if($type){
                $a = '2b';
                $b = '2x';
                $field = "`2b`";
            }else{
                $a = '2x';
                $b = '2b';
                $field = "`2x`";
            }
            $key_str = implode(',',$new_keys);

            $res = $this->db->query("select `2x`,`2b` from `tob_maps` where $field in($key_str)")->fetchall();
            
            //转成和  result  一样的格式
            $new_cache=array();
            foreach($res as $row){
                Log::writelog($val_len);
                $new_cache[$row[$a]][] = $result[$row[$a]][] = substr($row[$b],$val_len);
            }

            //将新db的值设置成缓存
            if(!empty($new_cache)){
                $this->cache->setMulti($new_cache);
            }

            //如果没有查到，则返回对应的key为空
            foreach($new_keys as $new_one){
                if(!array_key_exists($new_one,$new_cache)){
                    $result[$new_one]=[];
                }
            }            
            Log::writelog("read db..");
        }

        
        $new_result=array();
        foreach($result as $cache_key=>$value){
            $tmp = substr($cache_key,$key_len);
            $new_result[$tmp] = $value;
        }
        return $new_result;
    }

    /**
     * 人工新建或者修正映射关系或者删除
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function mapping_save($param){
        if(empty($param['src'])) throw new Exception("Error Processing Request src", 1);
        if(empty($param['src_no'])) throw new Exception("Error Processing Request src_no", 1);
        if(empty($param['cv_id'])) throw new Exception("Error Processing Request cv_id", 1);
        $src = (int)$param['src'];
        if(!in_array($src,$this->src_config)) throw new Exception("Error Processing Request src", 1);
        $cv_id = (int)$param['cv_id'];
        $src_no = (int)$param['src_no'];
        if(empty($cv_id) || empty($src_no)) throw new Exception("Error Processing Request cv_id or src_no", 1);
        
        if($src_no > 0){
            $delete_key[] = $tox = $src.$src_no;
            $delete_key[] = $tob = '1'.$src.$cv_id;
            $this->db->query("replace into `tob_maps`(`2x`,`2b`) values($tox,$tob)");
        }else{
            $delete_key[] = $key = $src.abs($src_no);

            $res = $this->db->query("select `2b` from `tob_maps` where `2x`='$key'")->fetch();
            $delete_key[] = $res['2b'];
            $this->db->query("delete from `tob_maps` where `2x`='$key' limit 1");
        }
        if($src == 89) $this->send_hunter(array('icdc_id'=>$cv_id,'toh_id'=>$src_no));
        $this->cache->deleteMulti($delete_key);
        return 'success';
    }

    /**
     * 单进程守护寻重操作 程序定位单进程，若需多进程处理，再修改程序
     * @return [type] [description]
     */
    public function unique_query(){
        Log::write_log("queue process ....");
        $res = $this->db->query("select * from `tob_maps_data` order by updated_at asc limit 100")->fetchall();
        if(empty($res)){
            Log::writelog("Before $time no data will sleep 100s...");
            sleep(100);
        }else{
            foreach($res as $row){
                $src_no = (int)$row['src_no'];
                $src = (int)$row['src'];
                $cv_id = $this->algorithm_query($row['src_data']) ;
                $where = "src='$src' and src_no='$src_no'";

                //如果没有拿到，则下次再继续
                if(empty($cv_id)){
                    $this->db->query("update `tob_maps_data` set `counter` = `counter`+1 where $where");
                }else{
                    //先建立mapping
                    try{
                        $this->mapping_save(array('src'=>$src,'src_no'=>$src_no,'cv_id'=>$cv_id));
                    }catch(Exception $e){
                        Log::writelog("mapping faild...");
                    }
                    //再从队列中删除
                    $this->db->query("delete from `tob_maps_data` where $where");
                }
            }
        }

        $this->unique_query();
    }


    /**
     * [algorithm_query description]
     * 进行去重算法接口查询 用返回的第一个简历id做mapping关系
     * key: !@#$%^&*()_+{":}
     * contact_decrypt(str,key);
     * contact_encrypt(str,key);
     * @return 返回b端简历id
     */
    private function algorithm_query($param):int
    {
        $res = $this->api->client(array(
            'c'=>'CVUniq',
            'm'=>'query',
            'p'=>array('-1'=>$param)
        ));
        $resume_id = 0;
        foreach ($res["results"]["-1"] as $row) {
            $resume_id = empty($row[0]) ? 0 : $row[0]; break;
        }
        Log::write_log("cvuniq resume_id $resume_id...");
        return $resume_id;
    }

    /**
     * 存在的意义：当icdc简历发生更新后
     *
     * de_id  删除的简历id   4种组合方案
     * cv_id  保留的简历id
     *
     * 1、如果de_id > 0 && cv_id > 0   查del是否有值 和 cvid是否有值  
     * @return [type] [description]
     */
    private function query($param){
        //1、如果同时为空
        if(empty($param['de_id']) && empty($param['cv_id'])){
            throw new Exception("Error Processing Request de_id and cv_id", 1);
        }

        $param['de_id'] = (int)$param['de_id'];
        $param['cv_id'] = (int)$param['cv_id'];

        //2、删除map  cv_id为空
        if($param['de_id'] > 0 && $param['cv_id'] == 0){
            $del_arr=[];
            
            foreach($this->src_config as $src){
                $del_arr[] = "1".$src.$param['de_id'];
            }
            
            $ids = implode(',',$del_arr);
            $this->db->query("delete from `tob_maps` where 2b in($ids)");
            $this->cache->deleteMulti($del_arr);
        }

        //3、如果存在map，不更新，并通知src=89的
        if($param['cv_id'] > 0 && $param['de_id'] == 0){
            $cache_key=[];
            foreach($this->src_config as $src){
                $cache_key[]="1".$src.$param['cv_id'];
            }
            $ids = implode(',',$cache_key);
            $res = $this->db->query("select * from `tob_maps` where 2b in($ids)")->fetchall();
            $res = empty($res) ? array() : $res;

            $send_data=[];
            foreach($res as $row){
                $suffix = substr($row['2b'],0,3);
                if($suffix == '189') $send_data[$param['cv_id']][] = substr($row['2x'],2);
            }
            
            if(!empty($send_data)) $this->send_hunter($send_data);
        }

        //4、如果是icdc_id 发生了变更，将旧的de_id => cv_id
        if($param['cv_id'] > 0 && $param['de_id'] > 0){
            
            $del_arr=[];
            $cv_arr=[];
            foreach($this->src_config as $src){
                $del_arr[] = "1".$src.$param['de_id'];
                $cv_arr[]='1'.$src.$param['cv_id'];
            }

            $ids = implode(',',$del_arr);
            $res = $this->db->query("select * from `tob_maps` where 2b in($ids)")->fetchall();
            $res = empty($res) ? array() : $res;

            foreach($res as $r){
                $new_data = substr($r['2b'],0,3).$param['cv_id'];
                $this->db->query("update `tob_maps` set 2b='$new_data' where 2x='{$r['2x']}' and 2b='{$r['2b']}' limit 1");
            }

            $new_ids = implode(',',$cv_arr);
            $res = $this->db->query("select * from `tob_maps` where 2b in($new_ids)")->fetchall();
            $res = empty($res) ? array() : $res;

            $send_data=[];
            foreach($res as $row){
                $suffix = substr($row['2b'],0,3);
                if($suffix == '189'){
                    $send_data['icdc_id'] = $param['cv_id'];
                    $send_data['toh_id'] = substr($row['2x'],2);
                }
            }
            if(!empty($send_data)) $this->send_hunter($send_data);
        }
        return 1;
    }

    /**
     * *根据当前参数 和 去重查到的id 比较判断谁更新
     * 1、本地参数                           local_info
     * 2、根据id查询icdc的id的简历信息       icdc_info
     * 3、get_diff() 原型抄过来
     * 4、将结果告知忠池 (异步发送)
     * @return [type] [description]
     */
    private function send_hunter(array $send_data)
    {
        $this->hunter->client(array(
            'c'=>'apis/logic_resume_api',
            'm'=>'resume_update_notice',
            'p'=>$send_data
        ),false,false,true);
    }
}