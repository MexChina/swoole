<?php
/**
 * 作用  全库读数据，从里面筛选出src=89 并且没有被删除的   然后将简历id推送到hunter接口去。
 *
 * 
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Worker;
class Hunter extends \Swoole\Core\App\Controller{
    private $db;
    private $hunter;
    private $page_size=10;

	public function init(){
		$this->db = $this->db("new_icdc_".$this->swoole->worker_id);
		$this->hunter = new Worker("toh_resumes_offline");
	}

	public function index(){
        $this->init();

        //统计有多少条
        $start_time = number_format(microtime(true), 8, '.', '');
        $result = $this->db->query("select count(1) as `total` from resumes_maps where is_deleted='N' and src=89")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
		$runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh used:$runtime");

     	//进行分页处理
        for($page=1;$page<=$page_total;$page++){
	        
	        $start_out_time = $start_time = number_format(microtime(true), 8, '.', '');
            $result = $this->db->query("SELECT resume_id,src_no FROM `resumes_maps` WHERE id >= (SELECT id FROM `resumes_maps` where is_deleted='N' and src=89 ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) and is_deleted='N' and src=89 ORDER BY id asc LIMIT $this->page_size")->fetchall();
            $current_count = count($result);
            $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            Log::write_log("icdc_{$this->swoole->worker_id} $page/$page_total count:$current_count used:$runtime");

            foreach ($result as $r){
                $this->hunter->client(array(
		            'c'=>'apis/logic_resume_api',
		            'm'=>'resume_update_notice',
		            'p'=>array(
		            	'icdc_id'=>$r['resume_id'],
		            	'toh_id'=>$r['src_no']
		            )
		        ),false,false,true);
            }
 
	        $runtime    = number_format(microtime(true), 8, '.', '') - $start_out_time;
            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total} used:$runtime");
        }

        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成");
    }
}