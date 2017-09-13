<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/1/6
 * Time: 14:31
 */

namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\App\Algorithm\Api;
use Swoole\Core\Lib\Worker;
class Refresh extends \Swoole\Core\App\Controller{
    private $db;
    private $page_size = 1000;
    private $icdc;
    private $save_count = 0;

    public function init(){
        $this->db = $this->db("slave_icdc_".$this->swoole->worker_id);
    }

    public function __construct(){
        $this->icdc = new Worker("icdc_basic");
    }

    public function index(){
        $this->init();
        $result = $this->db->query("select count(1) as `total` from resumes")->fetch();
        $all_total = $result['total'];
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh.......");

        System::exec_time();
        $refresh_count = 0;

        for($page=1;$page<=$page_total;$page++){
            $start_time = number_format(microtime(true), 8, '.', '');
            $start_memory = memory_get_usage();
            $resume_ids=[];
            $result = $this->db->query("SELECT id FROM `resumes` WHERE id >= (SELECT id FROM `resumes`  ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                $resume_ids[]=$r['id'];
            }
            if (empty($resume_ids)) {
                // Log::write_log("icdc_{$this->swoole->worker_id}  $page  $page_total 0 ...");
                continue;
            }
            $ids = implode(',',$resume_ids);
            $res = $this->db->query("select id,cv_tag,cv_trade,cv_entity from resumes_algorithms where id in($ids)")->fetchall();
            $new_resume_ids=[];
            foreach($res as $r){
                if(empty($r['cv_tag']) && empty($r['cv_entity']) && empty($r['cv_trade'])){
                    $new_resume_ids[]=$r['id'];
                }
            }

            if(empty($new_resume_ids)) continue;

            $this->api($new_resume_ids);

            $count_ids = count($new_resume_ids);
            $refresh_count += $count_ids;
            $this->logger(['total' => $all_total, 'icdc_table' => "icdc_{$this->swoole->worker_id}", 'refresh_count' => $refresh_count, 'count' => $count_ids, 'ids' => $new_resume_ids]);

            $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $str   = " runtime：{$runtime}s";
            $memory_use = number_format((memory_get_usage() - $start_memory) / 1024, 2);
            $str .= " memory：{$memory_use}kb";
            Log::write_log("icdc_{$this->swoole->worker_id}  $page  $page_total  $str ...");
            unset($result,$resume_ids,$count_ids);
        }

        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }

    public function logger($msg){
        $destination = "/opt/wwwroot/api/log/Refresh/refresh_leak_ids" . date("Ymd") . ".log";
        if (!is_string($msg)) {
            $msg = json_encode($msg,JSON_UNESCAPED_UNICODE);
        }
        $log_id = getmypid();
        $log_info = date("Y-m-d H:i:s")."\t$log_id\t$msg\r\n";
        echo $log_info;
        return error_log($log_info, 3, $destination);
    }

    public function api($ids){
        $obj = new Api();
        foreach($ids as $id){
            $compress = $this->compress($id);
            if(empty($compress)) continue;
            $trade = $obj->cv_trade($compress);
            $entity = $obj->cv_entity($compress);
            $tag = $obj->cv_tag($compress);
            //$edu = $obj->cv_education($compress);

            $data=array();
            if($trade) $data['cv_trade'] = json_encode($trade,JSON_UNESCAPED_UNICODE);
            if($entity) $data['cv_entity'] = json_encode($entity,JSON_UNESCAPED_UNICODE);
            if($tag) $data['cv_tag'] = json_encode($tag,JSON_UNESCAPED_UNICODE);
            /*if($edu){
                $data['cv_education'] = json_encode($edu['cv_education'],JSON_UNESCAPED_UNICODE);
                $data['cv_degree'] = $edu['cv_degree'];
            }*/
            if(empty($data)) continue;

            $this->save_count++;
            $this->logger(['icdc_table' => "icdc_{$this->swoole->worker_id}", 'all_save_count' => $this->save_count, 'id' => $id]);


            $this->icdc->client(array(
                'c'=>'resumes/Logic_algorithm',
                'm'=>'save',
                'p'=>array(
                    'id'=>$id,
                    'data'=>$data
                ),
            ),true,true,true);
        }
    }


    public function compress($id){
        $result = $this->db->query("select * from resumes_extras where id=$id")->fetch();
        return empty($result['compress']) ? '' : json_decode(gzuncompress($result['compress']), true);
    }


}