<?php
namespace Swoole\App\Share;
use Swoole\Core\AppServer;
use Swoole\Core\Log;
use Swoole\Core\App\Controller;
use Swoole\Core\Lib\Gearman;

class Share extends Controller {

    private $start;
    private $linepertime = 1000;

    private $gmclient;
    private $trade_table;
    private $place_table;
    private $company_table;
    private $source_db;
    private $target_db;
    private $data_config;

    
    public function init(){
        $this->trade_table = AppServer::$tables['trade_dic'];
        $this->place_table = AppServer::$tables['place_dic'];
        $this->company_table = AppServer::$tables['company_table'];
        $this->source_db = AppServer::db("toc_grab");
        $this->target_db = AppServer::db('shares');
//        $this->gmclient = new Gearman('221.228.230.204', 12528,'cv_trade_online',true);
        $this->gmclient = new Gearman('221.228.230.204', 12528,'corp_tag',true);
        $this->data_config = AppServer::$config->get("data");
    }
    
    public function index(){
        $this->trade();
        $this->place();
        $this->shares();
        $this->finance();
        $this->manager();
    }
    
    /**
     * 更新trade表
     */
    public function trade(){
        $resource = $this->source_db->query("SELECT trade_type from shares GROUP BY trade_type");
        $result = $resource->fetchall();
        $count = count($result);
        Log::write_log("shares_trade_dic 表共 $count 需要更新......");

        $result = is_array($result) ? $result : array();
        $values = '';
        foreach ($result as $r) {
            $key = $r['trade_type'];
            $key_value = $this->trade_table->get($key);
            $trade_data = $this->trade_data($key);
            if (empty($key_value)) {
                $values .= "('" . addslashes($key) . "'," . time() .",".$trade_data[0].",'".$trade_data[1]. "'),";
            }
        }
        if($values){
            $values = rtrim($values, ',');
            $sql = "replace into shares_trade_dic (`trade`,`created`,`eid`,`trade_alias`) values $values";
            $this->target_db->query($sql);
            $resource = $this->target_db->query("select id,trade from shares_trade_dic");
            $result = $resource->fetchall();
            foreach ($result as $r) {
                $this->trade_table->set($r['trade'], array('id' => $r['id']));
            }
        }
        Log::write_log("shares_trade_dic 表更新完成......");
    }

    
    /**
     * 更新place表
     */
    public function place(){
        $resource = $this->source_db->query("SELECT listing_place from shares GROUP BY listing_place");
        $result = $resource->fetchall();
        $count = count($result);
        Log::write_log("shares_place_dic 表共 $count 需要更新......");
        $result = is_array($result) ? $result : array();
        $values = '';
        foreach($result as $r){
            $key = $r['listing_place'];
            $key_value = $this->place_table->get($key);
            if(empty($key_value)){
                $values .= "('".addslashes($key)."','".time()."'),";
            }
        }
        if($values){
            $values = rtrim($values,',');
            $sql = "replace into shares_place_dic (`name`,`created`) values $values";
            $this->target_db->query($sql);
            $resource = $this->target_db->query("select id,name from shares_place_dic");
            $result = $resource->fetchall();
            foreach($result as $r){
                $this->place_table->set($r['name'],array('id'=>$r['id']));
            }
        }
        Log::write_log("shares_place_dic 表更新完成......");
    }

    /**
     * 处理主表
     */
    public function shares(){
        $resource = $this->source_db->query("select count(1) as mycount from shares");
        $res = $resource->fetch();
        $count = $res['mycount'];
        Log::write_log("shares 表共 $count 需要更新......");
        $this->start = 1;
        $j = $count/$this->linepertime;
        for($i=0;$i<=$j;$i++){
            $res2 = $this->source_db->query("SELECT a.code,a.name,a.full_name,a.en_name,a.trade_type,a.listing_place,b.total FROM shares as a,shares_total as b where a.`code` = b.`code` and a.id >= {$this->start} ORDER BY a.id asc LIMIT {$this->linepertime}");
            $result = $res2->fetchall();
            if(is_array($result) && $result) {

                $values = "";
                foreach ($result as $r) {
                    $values .= "('";
                    //code
                    $values .= $r['code'];
                    $values .= "',";
                    //company_id
                    $values .= $this->company_id($r['code'],$r['name']);
                    $values .= ",'";
                    //company_name
                    $values .= addslashes($r['name']);
                    $values .= "','";
                    //company_fullname_cn
                    $values .= addslashes($r['full_name']);
                    $values .= "','";
                    //company_fullname_en
                    $values .= addslashes($r['en_name']);
                    $values .= "',";
                    //total
                    $values .= $r['total'];
                    $values .= ",";
                    //trade_id
                    $values .= $this->trade_table->get($r['trade_type'])['id'];
                    $values .= ",";
                    //listing_id
                    $values .= $this->place_table->get($r['listing_place'])['id'];
                    $values .= ",'";
                    //created
                    $values .= time();
                    $values .= "'),";
                }
                $values = rtrim($values, ',');
                $sql = "replace into shares values $values";
                $this->target_db->query($sql);
                $this->start = $this->start + $this->linepertime;
            }
        }
        Log::write_log("shares 表更新完成......");
    }

    /**
     * 处理manager表
     */
    public function manager(){
        $resource = $this->source_db->query("select count(1) as mycount from shares_manager");
        $res = $resource->fetch();
        $count = $res['mycount'];
        Log::write_log("shares_manager 表共 $count 需要更新......");
        $this->start = 1;
        $j = $count/$this->linepertime;
        for($i=0;$i<=$j;$i++){
            $res2 = $this->source_db->query("SELECT * from shares_manager where id >= {$this->start} ORDER BY id asc LIMIT {$this->linepertime}");
            $result = $res2->fetchall();
            if(is_array($result) && $result){
                $values="";
                foreach($result as $r){
                    $values .= "('".$r['code']."',";
                    $company_id = $this->company_table->get($r['code'])['id'];
                    $company_id = (int)$company_id ? $company_id : 0;
                    $values .= "'" . $company_id . "',";
                    $values .= "'".addslashes($r['resume'])."',";
                    $values .= "'".time()."'),";
                }
                $values = rtrim($values,',');
                $sql = "replace into shares_manager (`code`,`company_id`,`resume`,`created`) values $values";
                $this->target_db->query($sql);
                $this->start = $this->start + $this->linepertime;
            }
        }
        Log::write_log("shares_manager 表更新完成......");
    }

    /**
     * 同步金融信息表
     */
    public function finance(){
        $resource = $this->source_db->query("select count(1) as mycount from shares_finance");
        $res = $resource->fetch();
        $count = $res['mycount'];
        Log::write_log("shares_finance 表共 $count 需要更新......");
        $this->start = 1;
        $j = $count/$this->linepertime;
        for($i=0;$i<=$j;$i++){
            $res2 = $this->source_db->query("SELECT * from shares_finance where id >= {$this->start} ORDER BY id asc LIMIT {$this->linepertime}");
            $result = $res2->fetchall();
            if(is_array($result) && $result) {
                $values = "";
                foreach ($result as $r) {
                    $values .= "('" . $r['code'] . "',";
                    $company_id = $this->company_table->get($r['code'])['id'];
                    $company_id = (int)$company_id ? $company_id : 0;
                    $values .= "'" . $company_id . "',";
                    $values .= "'" . strtotime($r['close_date']) . "',";
                    $values .= "'" . $r['income'] * 10000 . "',";
                    $values .= "'" . $r['net_profit'] * 10000 . "',";
                    $values .= "'" . time() . "'),";
                }
                $values = rtrim($values, ',');
                $sql = "replace into shares_finance (`code`,`company_id`,`close_date`,`income`,`net_profit`,`created`) values $values";
                $this->target_db->query($sql);
                $this->start = $this->start + $this->linepertime;
            }
        }
        Log::write_log("shares_finance 表更新完成......");
    }

    /**
     * 调用接口获取公司ID
     * @param $code
     * @param $name
     * @return int|mixed
     */
    public function company_id($code,$name){
        $company_id = $this->shares_data($name);    //先取本地
        if($company_id == 0){                       //再走接口
            $this->gmclient->set_header(array(
                'uid' => 1,
                'uname' => 'client',
                'version' => 1,
                'signid' => 2132,
                'provider' => 'shares',
                'ip' => 1232321
            ));

            $request = array(
                'cv_id' => '',
                'work_list' => [array(
                    'position' => '',
                    'company_name' => $name,
                    'work_id' => $code,
                    'desc' => '',
                    'industry_name' => ''
                )]
            );
            $response = $this->gmclient->client($request);
            $company_id = $response['result'][0]['company_id'];
            $company_id = (int)$company_id;
        }

        if($company_id){
            $this->company_table->set($code,array('id'=>$company_id));
        }
        return $company_id;
    }

    public function __destruct() {
        unset($this->trade_table);
        unset($this->place_table);
        unset($this->company_table);
        $this->source_db->close();
        $this->target_db->close();
    }

    private function shares_data($key){
        return isset($this->data_config['share'][$key]) ? $this->data_config['share'][$key] : 0;
    }

    private function trade_data($key){
        return isset($this->data_config['trade'][$key]) ? $this->data_config['trade'][$key] : array(0,'');
    }
}