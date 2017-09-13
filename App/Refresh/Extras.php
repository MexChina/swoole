<?php
/**
 * 将就的压缩包重构成新的压缩包
 * 查看表空间大小
 *
 * select concat(round(sum(data_length/1024/1024/1024),2),'GB') as data_length 
 * from information_schema.tables where table_schema='icdc_0' and table_name = 'resumes_extras';
 *
 * 
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Worker;
class Extras extends \Swoole\Core\App\Controller{
    private $db;            //源库
    private $ne_db;         //目标库
    private $worker;

    public function init(){}

    public function index(){
        $this->db = $this->db("new_icdc_".$this->swoole->worker_id);
        $this->new_db = $this->db("new_icdc_".$this->swoole->worker_id);

        $result = $this->db->query("select * from resumes_extras",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
            $this->check($row);
        }
        $result->close();

        Log::writelog("icdc_".$this->swoole->worker_id." complete...");
    }


 

    public function check($row){
        $compress1 = gzuncompress($row['compress']);
        $compress = json_decode($compress1, true);
        
        if(!is_array($compress)) $compress=array('basic'=>'','work'=>'','education');
        $field = '';
        foreach($compress as $k=>$v){
            $field .= "'".$k."','".addslashes(json_encode($v,JSON_UNESCAPED_UNICODE))."',";
        }
        $field = rtrim($field,',');
        // if(empty($field)) return;

        $sql = "replace into extras(`id`,`compress`,`updated_at`,`created_at`) values ({$row['id']},COLUMN_CREATE($field),'{$row['updated_at']}','{$row['created_at']}')";
        $this->new_db->query($sql);
        Log::writelog($row['id']." success...");
    }

    public function __destruct(){
        $this->db->close();
        $this->new_db->close();
    }
}