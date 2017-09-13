<?php
/**
 * 处理数据库中简历数据中birth： 
 * “xx 年01月01日” OR  “xx 年1月1日”  OR  “xx 年01月1日” OR  “xx 年1月01日” 
 * 数据修改成  “xx年” 
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
class Address extends \Swoole\Core\App\Controller{
    private $db;
    private $page_size=1000;

    public function init(){}

    /**
     * 24个库 主表进程
     * @return [type] [description]
     */
    public function index(){
        $this->db = $this->db("icdc_".$this->swoole->worker_id);
        $result = $this->db->query("select count(1) as `total` from resumes")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id}, have {$page_total} to count");
        
        for($page=1;$page<=$page_total;$page++){
            $resume_ids=[];
            
            $result = $this->db->query("SELECT id FROM `resumes` WHERE id >= (SELECT id FROM `resumes` ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r){
                $resume_ids[]=$r['id'];
            }

            if(empty($resume_ids)){
                Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total} 0");
                continue;
            }

            $ids = implode(',',$resume_ids);
            $res = $this->db->query("select * from `resumes_extras` where id in($ids)")->fetchall();
            if(empty($res)) continue;
        
            foreach($res as $row){
                $compress = json_decode(gzuncompress($row['compress']), true);

                if($compress['basic']['address'] == 81){
                    $compress['basic']['address'] = "3973";
                    $compress = addslashes(gzcompress(json_encode($compress)));
                    $this->db->query("update `resumes_extras` set `compress`='$compress' where id='{$row['id']}'");
                    $msg = date("Y-m-d H:i:s")."\t".$row['id']."\t".$birth."\r\n";
                    error_log($msg,3,SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/address_".$this->swoole->worker_id);
                }
            }

            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total}");
        }
        $this->db->close();
        Log::write_log("icdc_{$this->swoole->worker_id},".json_encode($this->source));
    }
}