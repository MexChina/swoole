<?php
/**
 * 将就的压缩包重构成新的压缩包
 * 查看表空间大小
 * 压缩包为空
 * 压缩包解压失败
 * 压缩包解压成功但为空
 * 压缩包解压成功长度小于10
 * 压缩包解压成功没有work的key
 * 压缩包解压成功work长度小于10
 * 压缩包解压成功的长度大于20
 * 找出了这样的id后，在根据id 取出src<80 并且updated_at 最新
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
class ResetAlgorithm extends \Swoole\Core\App\Controller{
    private $db;            //源库
    private $new_db;         //目标库

    private $db_name;

    public function init(){}

    public function index(){
        $this->db = $this->db("slave_icdc_".$this->swoole->worker_id);
        $this->new_db = $this->db("slave_icdc_".$this->swoole->worker_id);
        $this->allot=$this->db("icdc_allot");

        $this->redis = new \RedisCluster(NULL,['192.168.8.116:7105','192.168.8.115:7105','192.168.8.114:7105','192.168.8.113:7105','192.168.8.116:7106','192.168.8.115:7106',
                '192.168.8.114:7106','192.168.8.113:7106','192.168.8.116:7107','192.168.8.115:7107','192.168.8.114:7107','192.168.8.113:7107',
                '192.168.8.116:7108','192.168.8.115:7108','192.168.8.114:7108','192.168.8.113:7108']);

        $result = $this->db->query("select id,column_json(data) as data from algorithms",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
            $this->check($row);
        }

        $result->close();
        Log::writelog("icdc_".$this->swoole->worker_id." complete...");
    }

    public function check($row){
        $arr = json_decode($row['data'],true);
        $resume_id = $row['id'];
        if(is_array($arr) && !isset($arr['compress'])){     //db是好的数据
            $redis_data = $this->redis->hGetAll($resume_id);

            if(isset($redis_data['compress'])){
                Log::write_log("$resume_id redis data error");
                $this->redis->del(array($resume_id));
                $arr['updated_at'] = time('Y-m-d H:i:s');
                $this->redis->hMset($resume_id,$arr);
            }else{
                Log::write_log("$resume_id redis and db ok");
            }

        }else{
            Log::write_log("$resume_id db error");
            $this->new_db->query("update algorithms set data='' where id=$resume_id");
            $this->allot->query("replace into algorithm_jobs(resume_id) values ($resume_id)");
            $this->redis->del(array($resume_id));
        }
    }

    
 
    public function __destruct(){
        $this->db->close();
        $this->new_db->close();
    }
}