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
use Swoole\Core\Lib\Worker;
class SelectBadCompress extends \Swoole\Core\App\Controller{
    private $db;            //源库
    private $new_db;         //目标库
    private $worker;
    private $db_name;

    public function init(){}

    public function index(){
        $this->db = $this->db("slave_icdc_".$this->swoole->worker_id);
        $this->new_db = $this->db("slave_icdc_".$this->swoole->worker_id);
        $this->db_name = "icdc_".$this->swoole->worker_id;

        $result = $this->db->query("select id,compress,updated_at from resumes_extras",MYSQLI_USE_RESULT);
        while ($row=$result->fetch_assoc()){
            $this->check($row);
        }
        $result->close();

        Log::writelog("icdc_".$this->swoole->worker_id." complete...");
    }

    public function check($row){
        $id = (int)$row['id'];
        if($id <= 0) return;

        if(empty($row['compress'])){
            // $this->map($id,$row['updated_at'],'no compress');
            return;
        }

        $compress = json_decode(gzuncompress($row['compress']), true);
        
        if(!is_array($compress)){
            // $this->map($id,$row['updated_at'],'gzuncompress error');
            return;
        }

        // $compress_str = json_encode($compress);
        // if(strlen($compress_str) < 20){
        //     $this->map($id,$row['updated_at'],'compress short');
        //     return;
        // }


        if(!isset($compress['work'])){
            // if(count($compress['work']) > 20){
                $this->map($id,$row['updated_at'],'work null');
                return;
            // }

            // $work_str = json_encode($compress['work']);
            // if(strlen($work_str) < 20){
            //     $this->map($id,$row['updated_at']);
            //     return;
            // }
        }

        // if(isset($compress['education'])){
        //     if(count($compress['education']) > 20){
                // $this->map($id,$row['updated_at'],'education null');
                // return;
        //     }

            // $edu_str = json_encode($compress['education']);
            // if(strlen($edu_str) < 20){
            //     $this->map($id,$row['updated_at']);
            //     return;
            // }
        // }
    }

    public function map($resume_id,$updated_at,$match){
        $updated_at = $this->db_name.".".$updated_at;
        $resource = $this->new_db->query("select resume_id,src,src_no,is_deleted,updated_at from resumes_maps where resume_id=$resume_id");
        if($resource == false){
            error_log($updated_at."\t".$resume_id."\t0\t0\tno select map\n",3,"/opt/log/compress_bad_list");
            return;
        }
        $result = $resource->fetchall();
        $data=[];
        foreach($result as $row){
            if($row['is_deleted'] == 'Y'){
                continue;
            }

            if($row['src'] > 80){
                continue;
            }

            $time = strtotime($row['updated_at']);

            $key = isset($data[$time]) ? $time+1 : $time;
            $data[$key] = array($row['src'],$row['src_no']);
        }
        if(empty($data)){
            error_log($updated_at."\t".$resume_id."\t0\t0\tno data map\n",3,"/opt/log/compress_bad_list");
            return;
        }

        krsort($data);
        $new_data = current($data);
        if($new_data){
            error_log($updated_at."\t".$resume_id."\t".$new_data[0]."\t".$new_data[1]."\t$match\n",3,"/opt/log/compress_bad_list");
        }
        Log::write_log($resume_id."\t".json_encode($new_data));
    }
 
    public function __destruct(){
        $this->db->close();
        $this->new_db->close();
    }
}