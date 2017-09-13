<?php
/**
 * 统计2016年 10月 一来库中简历的数量以及简历的map=>csv文件
 */

namespace Swoole\App\Refresh;
use Swoole\Core\Log;
class Count extends \Swoole\Core\App\Controller{
	private $db;
	private $source;
	private $page_size=1000;

	public function init(){}

	/**
	 * 24个库 主表进程
	 * @return [type] [description]
	 */
	public function index(){
		$this->db = $this->db("master_icdc_".$this->swoole->worker_id);
	    $bi = $this->db("bi");
		$result = $this->db->query("select count(1) as `total` from resumes")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id}, have {$page_total} to count");
        for($page=1;$page<=$page_total;$page++){
        	$resume_ids=[];
            $result = $this->db->query("SELECT id,created_at FROM `resumes` WHERE id >= (SELECT id FROM `resumes` ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r){
            	if(strtotime($r['created_at']) > strtotime("2016-10-01 00:00:00")){
            		$resume_ids[]=$r['id'];
            	}
            }

            if(empty($resume_ids)){
            	Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total} 0");
            	continue;
            } 
            $ids = implode(',',$resume_ids);
            $res = $this->db->query("select src,src_no,resume_id from resumes_maps where resume_id in($ids)")->fetchall();
            if(empty($res)) continue;
	    
	    $values='';
            foreach($res as $row){
            	//$values .= "('{$row['src']}','{$row['src_no']}','{$row['resume_id']}'),";
            	$values .= "('".$row['src']."','".$row['src_no']."','".$row['resume_id']."')";
            	if(isset($this->source[$row['src']])){
            		$this->source[$row['src']]++;
            	}else{
            		$this->source[$row['src']]=1;
            	}
            }
	    if($values){
		$values = rtrim($values,',');
		$bi->query("insert into map(`src`,`src_no`,`resume_id`) values $values");
	    }

            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total}");
        }
        Log::write_log("icdc_{$this->swoole->worker_id},".json_encode($this->source));
	}


	/**
	 * 简历maps输出到csv
	 * @return [type] [description]
	 */
	private function csv($row){
		$str = $row['src'].",".$row['resume_id'].",".$row['src_no']."\n";
		error_log($str,3,"/opt/wwwroot/api/log/count.csv");
	}

}
