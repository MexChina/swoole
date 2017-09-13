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
class Worklong extends \Swoole\Core\App\Controller{
    private $db;            //源库
    private $ne_db;         //目标库
    private $worker;

    public function init(){}

    public function index(){
        $this->db = $this->db("master_icdc_".$this->swoole->worker_id);

        $result = $this->db->query("select id,compress from resumes_extras",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
            $this->check($row);
        }
        $result->close();

        Log::writelog("icdc_".$this->swoole->worker_id." complete...");
    }

    public function check($row){

        $long1 = $long2 = false;
        if(empty($row['compress'])) return;

        $compress = json_decode(gzuncompress($row['compress']), true);
        
        if(!is_array($compress)) return;
        if(isset($compress['work'])){
            if(count($compress['work']) > 20){
                $long1 = true;
            }
        }

        if(isset($compress['education'])){
            if(count($compress['education']) > 20){
                $long2 = true;
            }
        }

        if($long1 || $long2){
            Log::write_log($row['id']." error...");
            error_log($row['id']."\n",3,"/opt/log/longid");
        }
    }
 
    public function __destruct(){
        $this->db->close();
    }
}