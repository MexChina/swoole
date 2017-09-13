<?php
/**
 * 更新公司公交信息   不能使用子查询进行分页的原因是源库中的id不连续，会丢失一部分数据
 *
 * 要求：
 * 将 tobusiness.company_address_relation 和 tobusiness.company_traffic 数据
 * 更新到 gsystem.corporations_traffic_address、gsystem.corporations_traffic、gsystem.corporations_traffic_relation
 * 两张表分两个进程同步刷
 * bug 当page页码大于1400时，会被kill掉，原因待查
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
class Traffic extends \Swoole\Core\App\Controller{

    private $tobusiness_db;
    private $gsystem_db;
    private $bi_db;
    private $page_size = 1000;

    public function init(){
        $this->gsystem_db = $this->db('gsystem');
        $this->tobusiness_db = $this->db('tobusiness');
        $this->bi_db = $this->db('bi');
    }


    public function start(){
        $this->init();

        if($this->swoole->worker_id == 0){
            $this->address();
        }

        if($this->swoole->worker_id == 1){
            $this->traffic();
        }
    }

    /**
     * 进程 0 执行 刷公司地址信息
     */
    private function address(){
        Log::write_log("开始更新 corporations_traffic_address ...");
        System::exec_time();
        $res = $this->tobusiness_db->query("select count(1) as `count` from company_address_relation")->fetch();
        $count = $res['count'];
        $page_count = ceil($count/$this->page_size);
        if($page_count > 0) $this->bi_db->query("truncate table corporations_traffic_address");
        for($page=1;$page<=$page_count;$page++){

            $sql = "SELECT * FROM `company_address_relation` WHERE id <= (SELECT id FROM `company_address_relation` ORDER BY id desc LIMIT ".($page-1)*$this->page_size.", 1) ORDER BY id desc LIMIT $this->page_size";
            $result = $this->tobusiness_db->query($sql)->fetchall();
            $values = "";
            foreach($result as $r){
                $res2 = $this->gsystem_db->query("select id from regions where `name` = '{$r['city_name']}'")->fetch();
                if(empty($res2) || empty($res2['id'])) $res2['id']=0;
                $values .= "('" . $r['corporation_id'] . "','".$res2['id']."','" . addslashes($r['address']) . "'),";
            }
            $values = rtrim($values,',');
            if($values){
                $this->bi_db->query("insert INTO corporations_traffic_address(`corporation_id`,`city_id`,`address`) VALUES $values");
                Log::write_log("company_address_relation  $page / $page_count 更新成功...");
            }
        }
        Log::write_log("corporations_traffic_address 更新完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }

    /**
     * 进程 1 执行 刷公交信息
     */
    private function traffic(){
        Log::write_log("开始更新 corporations_traffic ...");
        System::exec_time();
        $res = $this->tobusiness_db->query("select count(1) as `count` from company_traffic")->fetch();
        $count = $res['count'];
        $page_count = ceil($count/$this->page_size);
        if($page_count > 0){
            $this->bi_db->query("truncate table corporations_traffic");
            $this->bi_db->query("truncate table corporations_traffic_relation");
        }
        $primary_id = 1;
        for($page=1;$page<=$page_count;$page++){
            $sql = "SELECT * FROM `company_traffic` WHERE id >= (SELECT id FROM `company_traffic` ORDER BY id asc LIMIT ".($page-1)*$this->page_size.", 1) ORDER BY id asc LIMIT $this->page_size";
            $result = $this->tobusiness_db->query($sql)->fetchall();
            $values1 = "";
            $values2 = "";
            foreach($result as $r){
                $values1 .= "('" . $primary_id . "','";
                $values1 .= $r['corporation_id'] . "','";
                $values1 .= $r['c_lng'] . "','";
                $values1 .= $r['c_lat']."','";
                $values1 .= $r['station_name']."','";
                $values1 .= $r['s_lng']."','";
                $values1 .= $r['s_lat']."','";
                $values1 .= $r['address_id']."','";
                $values1 .= $r['transportation']."','";
                $values1 .= $r['distance']. "'),";

                $address_arr = explode(';',$r['s_address']);
                foreach($address_arr as $a){
                    $values2 .= "('".$primary_id."','".$a."'),";
                }
                $primary_id++;
            }
            $values2 = rtrim($values2,',');
            $values1 = rtrim($values1,',');
            if($values1 && $values2){
                $this->bi_db->query("insert into corporations_traffic(`id`,`corporation_id`,`c_lng`,`c_lat`,`station_name`,`s_lng`,`s_lat`,`address_id`,`transportation`,`distance`) VALUES $values1");
                $this->bi_db->query("insert into corporations_traffic_relation(`tid`,`traffic_name`) VALUES $values2");
                Log::write_log("corporations_traffic  $page / $page_count 更新成功...");
            }
            unset($resource,$result,$values1,$values2,$address_arr);
        }
        Log::write_log("corporations_traffic 更新完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }

    public function __destruct(){
        $this->bi_db->close();
        $this->gsystem_db->close();
        $this->tobusiness_db->close();
    }
}