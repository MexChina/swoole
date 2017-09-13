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
class Resume extends \Swoole\Core\App\Controller{
    private $db;
    private $page_size = 100;

    public function init(){}


    public function index(){
        $this->db = $this->db("icdc_".$this->swoole->worker_id);
        $this->write = $this->db("icdc_allot");
        $result = $this->db->query("select count(1) as `total` from resumes where `is_deleted`='N' and `is_processing`='1'")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh.......");

        System::exec_time();
        for($page=1;$page<=$page_total;$page++){
	        $start_time = number_format(microtime(true), 8, '.', '');
            $start_memory = memory_get_usage();           
            $resume_ids=[];
            $result = $this->db->query("SELECT id FROM `resumes` WHERE id >= (SELECT id FROM `resumes` where `is_deleted`='N' and `is_processing`='1' ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) and `is_deleted`='N' and `is_processing`='1' ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                $resume_ids[]=$r['id'];
            }
            
            if (empty($resume_ids)) continue;
            

            $values = '';
            foreach($resume_ids as $id){
                $values .= "($id),";
            }
            $values = rtrim($values,',');
            $sql = "replace into algorithm_jobs(`resume_id`) values $values";
            $this->write->query($sql);
            
	        $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $str   = "{$runtime}s,";
            $memory_use = number_format((memory_get_usage() - $start_memory) / 1024, 2);
            $str .= "{$memory_use}kb";     
            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total},$str");
        }
        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }
}
