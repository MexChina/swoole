<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-11-24
 * Time: 下午8:25
 * 将gsystem中corporations_addresses 刷至gsystem_traffic
 *
 *
 */

ini_set('display_errors', 1);
class Refresh{
    private $db;
    private $page_size = 100;
    public function __construct($env){
        if($env == 1){  //开发环境
            $this->db = new PDO('mysql:host=192.168.1.201;port:3306;', 'devuser', 'devuser');
        }elseif($env == 2){ //测试环境
            $this->db = new PDO('mysql:host=10.9.10.6;port:3306;', 'bi', 'Vuf6m91PRGz8G.F*GJA0');
        }else{
            $this->db = new PDO('mysql:host=192.168.8.105;port:3307;', 'biuser', '30iH541pSBCU');
            //$this->db = new mysqli('192.168.8.101','jcsj_gs','0!TaaBsc7WQ!AfH','gsystem',3307);
        }
        $this->db->query('set names UTF8');
    }

    /** gearman client
     * @param $resume_ids
     * @return mixed
     */
    public function client($address){
        $client= new GearmanClient();
        $client->addServer("192.168.8.13",4730);
        $param['header']['product_name'] = 'gsystem';
        $param['header']['uname'] = 'jiqing.sun@ifchange.com';
        $param['header']['session_id'] = '22';
        $param['header']['user_ip'] = '127.0.0.1';
        $param['header']['local_ip'] = '10.9.10.6';
        $param['header']['log_id'] = uniqid(getmypid());
        $param['request']['c'] = 'Logic_traffic';
        $param['request']['m'] = 'sync_corporation_traffic';
        $param['request']['p'] = [ 'type' => 'gsystem', 'addresses' => $address];
        $send_data = msgpack_pack($param);
        $packedResponse = $client->doNormal("gsystem_basic", $send_data);
        $response = msgpack_unpack($packedResponse);
        return $response["response"]["results"];
    }

    /** log日志
     * @param $msg
     * @return bool
     */
    public function logger($msg){
        $destination = "/opt/log/refresh_gsystem_address.log";
        if (!is_string($msg)) {
            $msg = json_encode($msg,JSON_UNESCAPED_UNICODE);
        }
        $log_id = getmypid();
        $log_info = date("Y-m-d H:i:s")."\t$log_id\t$msg\r\n";
        echo $log_info;
        return error_log($log_info, 3,$destination);
    }

    public function run(){
        $this->logger("开始更新 gsystem_traffic corporations_traffic_address ...");
        $res = $this->db->query("select count(1) as `count` from gsystem.corporations_addresses")->fetch(PDO::FETCH_ASSOC);

        $count = $res['count'];
        $page_count = ceil($count/$this->page_size);
        $this->logger("gsystem_corporation_traffic have {$page_count} to refresh.......");

        $inital_start_time = number_format(microtime(true), 8, '.', '');
        for($page=1;$page<=$page_count;$page++){
            $start_time = number_format(microtime(true), 8, '.', '');
            $start_memory = memory_get_usage();
            $sql = "SELECT * FROM gsystem.`corporations_addresses` WHERE id >= (SELECT id FROM gsystem.`corporations_addresses` ORDER BY id asc LIMIT ".($page-1)*$this->page_size.", 1) ORDER BY id asc LIMIT $this->page_size";
            $result = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            $addresses = [];
            foreach($result as $r){
                if(empty($r['corporation_id']) || empty($r['address'])){
                    continue;
                }
                $addresses[] = [
                    'corporation_id' => $r['corporation_id'],
                    'address' => $r['address'],
                    'address_id' => 0
                ];
            }

            $count_addresses = count($addresses);
            $this->logger("refresh_gsystem_corporation_traffic  $page  $page_count {$count_addresses} ...");
            $response = $this->client($addresses);
            $gearman_return = !is_string($response) ? json_encode($response) : $response;
            $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $str   = " runtime：{$runtime}s";
            $memory_use = number_format((memory_get_usage() - $start_memory) / 1024, 2);
            $str .= " memory：{$memory_use}kb";
            $this->logger("refresh_gsystem_corporation_traffic  $page  $page_count $gearman_return $str ...");
            unset($result,$resume_ids,$gearman_return,$count_ids, $addresses);
        }
        $end_runtime    = number_format(microtime(true), 8, '.', '') - $inital_start_time;
        $this->logger("refresh_gsystem_corporation_traffic 刷库完成，用时： " . $end_runtime . 's');
    }
    
    public function start(){
        $this->logger("开始更新 gsystem_traffic corporations_traffic_address ...");
        $result = $this->db->query("select * from corporations_addresses limit 110000",MYSQLI_USE_RESULT);
        
        $i=0;
        $addresses = [];
        $page=1;
        while($r = $result->fetch_assoc()){
            if(empty($r['corporation_id']) || empty($r['address'])){
                continue;
            }

            $addresses[] = [
                'corporation_id' => $r['corporation_id'],
                'address' => $r['address'],
                'address_id' => 0
            ];
            
            $i++;
            
            if($i == 10){
                $this->logger("gsystem_corporation_traffic $page to refresh.......");
                $start_time = number_format(microtime(true), 8, '.', '');
                $start_memory = memory_get_usage();
                $response = $this->client($addresses);
                $gearman_return = !is_string($response) ? json_encode($response) : $response;
                $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
                $str   = " runtime：{$runtime}s";
                $memory_use = number_format((memory_get_usage() - $start_memory) / 1024, 2);
                $str .= " memory：{$memory_use}kb";
                $this->logger("refresh_gsystem_corporation_traffic  $page $gearman_return $str ...");

                $i=0;
                $addresses = [];
                $page++;
            }
        }
        
        $result->free();
        $this->db->close();
    }
    
}

$Refresh = new Refresh(3);
$Refresh->run();
//$Refresh->start();
