<?php
/**
 * 处理数据库中简历数据中birth： 
 * “xx 年01月01日” OR  “xx 年1月1日”  OR  “xx 年01月1日” OR  “xx 年1月01日” 
 * 数据修改成  “xx年” 
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Lib\Cache\Mredis;
class Algorithm extends \Swoole\Core\App\Controller{
	private $db;
	private $page_size=5000;
    private $redis;

	public function init(){
        $this->redis = new Mredis(array(
            0 => ["master" => "192.168.1.201:7000",'slot' => '0-5460', 'slave' => '192.168.1.201:7003'],
            1 => ["master" => "192.168.1.201:7001",'slot' => '5461-10922', 'slave' => '192.168.1.201:7004'],
            2 => ["master" => "192.168.1.201:7002",'slot' => '10923-16383', 'slave' => '192.168.1.201:7005'],
        ));

        $this->redis->connect();
    }

	/**
	 * 24个库 主表进程
	 * @return [type] [description]
	 */
	public function index(){
        echo "ssssssssssssssssssss\n";
	$this->init();
		$this->db = $this->db("icdc");
		//$result = $this->db->query("select count(1) as `total` from resumes")->fetch();
        //$page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id}, have 9000 to count");
        
        for($page=1;$page<=5000;$page++){
        	$resume_ids=[];
            
            $result = $this->db->query("SELECT id FROM `resumes` WHERE id >= (SELECT id FROM `resumes` ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) ORDER BY id asc LIMIT $this->page_size")->fetchall();


            foreach ($result as $r){
            	$resume_ids[]=$r['id'];
            }


            if(empty($resume_ids)){
            	Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total} 0");
            	continue;
            }

	   $res = $this->redis->hmgetall($resume_ids);
	   // var_dump($res);
	   
           /* $ids = implode(',',$resume_ids);
            $res = $this->db->query("select * from `resumes_algorithms` where id in($ids)")->fetchall();
            if(empty($res)) continue;
	       
            $data=[];
            foreach($res as $row){
                $key = $row['id'];unset($row['id']);
                foreach($row as $field=>$value){
		    if(empty($value)) continue;
                    $data[]=array(
                        'key'=>$key,
                        'field'=>$field,
                        'value'=>$value
                    );
                }
            }
            if(!empty($data)) $this->redis->hmset($data);*/
            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total}");
        }
        $this->db->close();
        $this->redis->close();
        Log::write_log("icdc_{$this->swoole->worker_id},".json_encode($this->source));
	}
}
