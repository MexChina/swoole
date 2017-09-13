<?php
namespace Swoole\App\Position;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
class Position extends \Swoole\Core\App\Controller{

    private $page_size = 1000;
    private $refresh_servers=array(
        'tag_predict', //cv_tag 的worker
        'title_recognize_server_new_format'
    );

    //刷库白名单字典
    private $algorithm_field=[
        'jd_tags'
    ];

    public function init(){
        $this->db = $this->db("position_".$this->swoole->worker_id);     //读
        foreach($this->refresh_servers as $s){
            $this->apiinit($s);
        }
    }
    public function index(){
        $this->init();
        $result = $this->db->query("select count(1) as `total` from positions where is_deleted='N'")->fetch();
        $page_total = 500;
        Log::write_log("position_{$this->swoole->worker_id} have {$page_total} to refresh.......");

        for($page=1;$page<=$page_total;$page++){
            $start_time = number_format(microtime(true), 8, '.', '');

            $resume_ids=[];
            $result = $this->db->query("SELECT a.id,a.name,a.architecture_name,b.industries,c.description,c.requirement FROM `positions` as a,`positions_excepts` as b,`positions_extras` as c WHERE a.id >= (SELECT id FROM `positions` where is_deleted='N' ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) and a.is_deleted='N' and a.id=b.id and a.id=c.id ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                if(!empty($r)){
                    $this->jd_tags($r);
                }
            }

            $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $str   = "{$runtime}s,"; 
            Log::write_log("position_{$this->swoole->worker_id},{$page}/{$page_total},$str");
        }

        Log::write_log("position_{$this->swoole->worker_id} 刷库完成");
    }

    //=================================================================================================================================
    private function jd_tags($compress){
        $id = $compress['id'];
        $param=array(
            'header'=>$this->header(),
            'request'=>array(
                'c' => 'jd_tag',
                'm' => 'get_jd_tags',
                'source'=>"2b",
                'p' => array(
                    'position' => $compress
                )
            )
        );

        $success = @$this->tag_predict->ping('data testing');
        if (!$success) {
            $this->apiinit("tag_predict");
        }
        $this->access("position_tag_predict",$param);
        $start_time = microtime(true);
        $return = $this->tag_predict->doNormal("tag_predict",json_encode($param));
        $time = (microtime(TRUE)-$start_time)*1000;
        $rs = msgpack_unpack($return);
        $this->access("position_tag_predict",$rs,"RSQ:",$time);
        
        if(empty($rs['response'])){
            error_log($idss,3,"/opt/log/jd_tag_timeout_ids");
            return null;
        }
        $rss = $rs['response'];

        $jd_tags='';
        if(isset($rss['results'])){
            $rss['results']['ref_zhiji'][0]['bundle']=$this->title($compress['name']);
            $jd_tags = empty($rss['results']) ? '' : json_encode($rss['results'],JSON_UNESCAPED_UNICODE);
        }else{
            error_log($idss,3,"/opt/log/jd_tag_timeout_ids");
            return null;
        }

        $time = date('Y-m-d H:i:s');
        $this->db->query("update positions_algorithms set `jd_tags`='$jd_tags',updated_at='$time' where id=$id");
    }
    public function title($name){

        $param=array(
            'header'=>$this->header(),
            'request'=>array(
                'c' => 'title_recognition',
                'm' => 'get_title_recognition',
                'p' => array(
                    'key' => $name
                )
            )
        );

        $success = @$this->title_recognize_server_new_format->ping('data testing');
        if (!$success) {
            $this->apiinit("title_recognize_server_new_format");
        }
        $this->access("title_recognize_server_new_format",$param);
        $start_time = microtime(true);
        $return = $this->title_recognize_server_new_format->doNormal("title_recognize_server_new_format",msgpack_pack($param));
        $time = (microtime(TRUE)-$start_time)*1000;
        $rs = msgpack_unpack($return);
        $this->access("title_recognize_server_new_format",$rs,"RSQ:",$time);

        return $rs['response']['err_no'] == 0 ? $rs['response']['results']['key']['level'] : 0;
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
        $this->db->close();
    }
}