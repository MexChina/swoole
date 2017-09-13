<?php
/**
 * 将resumes表的数据取出来原样再执行一下update操作 业务需求  2017-7-3
 * 1、内存监控去掉 每一页的内存使用很小
 * 2、时间改为3位小数即可
 */

namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
class Resume extends \Swoole\Core\App\Controller{
    private $db;
    private $page_size = 100;

    public function init(){}


    public function index(){
        $this->db = $this->db("new_icdc_".$this->swoole->worker_id);
        $result = $this->db->query("select count(1) as `total` from resumes where `is_deleted`='N'")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh.......");

        System::exec_time();
        for($page=1;$page<=$page_total;$page++){
	        $start_time = number_format(microtime(true), 3, '.', '');
          
            $result = $this->db->query("SELECT id,updated_at FROM `resumes` WHERE id >= (SELECT id FROM `resumes` where `is_deleted`='N' ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) and `is_deleted`='N' ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                $this->db->query("update resumes set updated_at='{$r['updated_at']}' where id={$r['id']}");
            }
 
	        $runtime    = number_format(microtime(true), 3, '.', '') - $start_time;
            $str   = "{$runtime}s";
            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total},$str");
        }
        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }
}
