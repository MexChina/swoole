<?php
/**
 * 验证库中简历id是否满足分库算法
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Worker;
use \SplFileObject;
class ResumesExtras extends \Swoole\Core\App\Controller{
    private $db;            //目标库 
    private $worker;

    public function init(){}

    public function index(){
        $this->db = $this->db("new_icdc_".$this->swoole->worker_id);
        $this->worker = new Worker('icdc_basic');
        // $this->new_db = $this->db("new_icdc_".$this->swoole->worker_id);
        // $this->table('resumes_extras');
        $this->getfile();
        Log::writelog("icdc_".$this->swoole->worker_id." complete...");
    }


    public function getfile(){
        $arr = new SplFileObject("/opt/wwwroot/api/Excel/ids2");
        foreach($arr as $id){
            $hash_key = (int)$id;
            $index = ($hash_key%8) + floor($hash_key/40000000) * 8;
            if($index == $this->swoole->worker_id){
                
            if($this->update_db($row['id'],$msg)){
                $this->update_cache($row['id'],$msg);
            }

                Log::writelog($hash_key."\t success...");
            }
        }
    }

    /**
     * 2、 table  根据传入的table进行读数据
     * @return [type] [description]
     */
    public function table(string $table){
        $result = $this->db->query("select * from `{$table}`",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
            $this->check($table,$row);
        }
        $result->close();
    }

    public function check($table,$row){
        $compress1 = gzuncompress($row['compress']);
        $compress = json_decode($compress1, true);
        if($compress == null){

            $msg = $row['id']."\t".$row['updated_at']."\t".$row['created_at']."\t";
            if($this->update_db($row['id'],$msg)){
                $this->update_cache($row['id'],$msg);
            }

            Log::writelog($msg);
            error_log($row['id']."\n",3,'/opt/log/icdc_extras.txt');
        }
    }

    /**
     * 更新数据库数据
     * @param  [type] $id   [description]
     * @param  [type] &$msg [description]
     * @return [type]       [description]
     */
    public function update_db($id,&$msg){
        $res = $this->worker->client(array(
                'c'=>'resumes/logic_resume',
                'm'=>'get_multi_all',
                'p'=>array(
                        'ids'=>[$row['id']],
                        'selected'=>''
                    )
            ),true,true);

        $new_compress = $res['results'][$row['id']];
        if(is_array($new_compress)){
            $cc = gzcompress(json_encode($new_compress));
            $cc = addslashes($cc);
            $this->ne_db->query("update $table set compress='$cc' where id=".$row['id']);

            $msg .= "db update success...";
            return true;
        }else{
            $msg .= "get rsumes info error";
            return false;
        }
    }


    /**
     * 更新缓存数据
     * @param  [type] $resume_id [description]
     * @return [type]            [description]
     */
    public function update_cache($resume_id,$msg){
        $this->worker->client(array(
            'c'=>"logic_refresh",
            'm'=>'cache',
            'p'=>array(
                'id'=>$resume_id,
                'model'=>"resumes/Model_resume_extra"
                )
        ),true,true);
        $msg .= "udpate cache success...";
    }

    public function __destruct(){
        $this->db->close();
        $this->new_db->close();
    }
}