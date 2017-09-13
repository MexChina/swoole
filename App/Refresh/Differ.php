<?php
/**
 * 验证库中简历id是否满足分库算法
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
class Differ extends \Swoole\Core\App\Controller{
    private $db;            //目标库 
    //要处理的表500*9*24
    private $tables=['contacts','resumes','resumes_algorithms','resumes_extras','resumes_flags','resumes_maps','resumes_update','users_contacts','users_resumes'];

    public function init(){}

    public function index(){
        $this->db = $this->db("icdc_".$this->swoole->worker_id);
        foreach($this->tables as $table){
            Log::writelog("start compress $table");
            $this->table($table);
        }
    }

    /**
     * 2、 table  根据传入的table进行读数据
     * @return [type] [description]
     */
    public function table(string $table){
        $result = $this->db->query("select * from $table",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
            $this->check($table,$row);
        }
        $result->close();
    }

    public function check($table,$row){
        $key = $table == 'resumes_maps' ? 'resume_id' : 'id';
        $hash_key = $row[$key];
        $suffix = ($hash_key%8) + floor($hash_key/40000000) * 8;
        if($suffix != $this->swoole->worker_id){
            Log::writelog($hash_key."\t".$suffix."\terror...");
            error_log(date("Y-m-d H:i:s")."\t".$table."\t".$hash_key."\n",3,'/opt/log/check_db_key.log');
        }else{
            Log::writelog($hash_key."\t".$suffix."\tok...");
        }
    }
}
