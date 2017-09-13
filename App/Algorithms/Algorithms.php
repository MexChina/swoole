<?php
/**
 * 算法刷库主进程
 */
namespace Swoole\App\Algorithms;
use Swoole\Core\Log;
class Algorithms extends \Swoole\Core\App\Controller{
    
    public function init(){}
    public function start(){
        
        //建立db链接
        $db = $this->db("slave_icdc_".$this->swoole->worker_id);
        
        //获取简历ID
        Log::write_log("start to select from icdc_".$this->swoole->worker_id);
        $resouce = $db->query("select id from resumes where is_deleted='N'",MYSQLI_USE_RESULT);
        
        $page=0;    //页码
        $box=[];    //存放页码的盒子
        Log::write_log("start to fetch resource....");
        while($row = $resouce->fetch_assoc()){
            
            //将简历id放入盒子中
            $box[]=$row['id'];
            $page++;
            
            //当盒子满了，开始处理,并置空盒子
            if($page==1000){
                $this->task(array('ids'=>$box,'db_id'=>$this->swoole->worker_id));
                $box=[];
                $page=0;
            }
        }
        Log::write_log("fetch resource complete....");
        $resouce->free();
        $db->close();
    }
}