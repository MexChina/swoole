<?php
/**
 * 处理数据库中简历数据中birth： 
 * “xx 年01月01日” OR  “xx 年1月1日”  OR  “xx 年01月1日” OR  “xx 年1月01日” 
 * 数据修改成  “xx年” 
 */
namespace Swoole\App\MM;
use Swoole\Core\Log;
use \SplFileObject;
class MM extends \Swoole\Core\App\Controller{

    private $page_size=100;
    private $task_num = 100;

    public function init(){}

    public function index(){
        $this->file();
    }

    public function file(){
        $idss = new SplFileObject("/tmp/resume_id.csv");
        $total = exec("wc -l /tmp/resume_id.csv");
        $page_total = ceil($total/$this->page_size);

        $ids=[];
        $page=1;

        foreach($idss as $id){
            $id = (int)$id;
            if($id <=0 ) continue;

            $key = ($id%8) + floor($id/40000000) * 8;
            if(count($ids[$key]) == $this->page_size || $page >= $page_total){
                $data['host'] = $key % 2 == 0 ? "192.168.8.130" : "192.168.8.132";
                $data['ids'] = $ids[$key];
                $data['key'] = "icdc_".$key;
                $this->send_data($data);
                $page++;
                $ids[$key]=[];
            }
            Log::write_log($id." ok");
            $ids[$key][]=$id;
        }

        foreach($ids as $key=>$v){
            $data['host'] = $key % 2 == 0 ? "192.168.8.130" : "192.168.8.132";
            $data['ids'] = $v;
            $data['key'] = "icdc_".$key;
            $this->send_data($data);
        }
        Log::write_log("over.....");
    }

    private function send_data($data){
        if($this->task_num > 0){
            $this->task($data,function(){
                Log::write_log("====ok====");
            },'index');
            $this->task_num--;
        }else{
            Log::write_log("will sleep 5");
            sleep(2);
            $this->task_num=100;
            $this->send_data($data);
        }
    }
}
