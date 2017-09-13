<?php
/**
 * 需求：需要将resumes_maps 中 src=9 的 结果取出来
 *
 * 如果resume_id 相同的 超过1个则 删除该简历
 * 否则 将简历压缩包中的薪资上线赋值给薪资下线
 *
 * 24个库的resumes_maps 同时读取 先将要删除的简历id读出来
 * SELECT resume_id from resumes_maps WHERE src=9 and is_deleted='N' GROUP BY resume_id HAVING count(resume_id) > 1
 *
 * 然后再跑将src=9的读出来进行修改
 * SELECT resume_id from resumes_maps WHERE src=9 and is_deleted='N' GROUP BY resume_id HAVING count(resume_id) = 1
 *
 * 遍历进行分表算法读出来进行处理
 *
 * 最后关闭所有连接
 * 2017-4-26
 */
namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Worker;
class Refreshyingcai extends \Swoole\Core\App\Controller{
    private $master_db;            
    private $worker;


    public function init(){}

    public function index(){
        //准备数据库连接
        $this->master_db = $this->db("master_icdc_".$this->swoole->worker_id);
        $this->worker = new Worker('icdc_refresh');
        $this->delete();
        $this->update();
    }
    /**
     * 只删简历  map等刷完后命令端执行
     * @return [type] [description]
     */
    private function delete(){
        Log::writelog("start delete icdc_{$this->swoole->worker_id}.resumes_maps ...");
        $result  = $this->master_db->query("SELECT resume_id from resumes_maps WHERE src=9 and is_deleted='N' GROUP BY resume_id HAVING count(resume_id) > 1")->fetchall();
        foreach($result as $r){
            $i++;
            $time = date('Y-m-d H:i:s');
            $this->master_db->query("update resumes_maps set is_deleted='Y',updated_at='$time' where src=9 and resume_id={$r['resume_id']}");
            $this->api_delete_resumes($r['resume_id']);
        }
        Log::writelog("end delete icdc_{$this->swoole->worker_id}.resumes_maps ...");
    }
    /**
     * 删除简历接口
     * @param  int    $resume_id [description]
     * @return [type]            [description]
     */
    private function api_delete_resumes(int $resume_id){
        $res = $this->worker->client(array(
                'c'=>'resumes/Logic_resume',
                'm'=>'del',
                'p'=>['ids'=>$resume_id]
            ),true,true);
        Log::writelog("icdc_{$this->swoole->worker_id}.{$resume_id}\tdelete success ".json_encode($res));
    }

    /**
     * 更新简历压缩包中的期望薪资
     * @return [type] [description]
     */
    private function update(){
        Log::writelog("start update icdc_{$this->swoole->worker_id}.resumes_extras ...");
        $result  = $this->master_db->query("SELECT resume_id from resumes_maps WHERE src=9 and is_deleted='N'")->fetchall();
        foreach($result as $r){
            $resumes_extra = $this->master_db->query("select `compress` from `resumes_extras` where id={$r['resume_id']}")->fetch();
            $compress = json_decode(gzuncompress($resumes_extra['compress']), true);

            $from = empty($compress['basic']['expect_salary_from']) ? 0 : $compress['basic']['expect_salary_from'];
            $to = empty($compress['basic']['expect_salary_to']) ? 0 : $compress['basic']['expect_salary_to'];
        
            if($from > 0 && $to > 0) continue;
            if($from == 0 && $to == 0) continue;
            if($to == 0 && $from >0){
                $compress['basic']['expect_salary_to'] = $from;
            }

            if($from == 0 && $to > 0){
                $compress['basic']['expect_salary_from'] = $to;
            }
            Log::writelog("icdc_{$this->swoole->worker_id}.{$r['resume_id']}\tupdate success...");
            $compress = addslashes(gzcompress(json_encode($compress)));
            $this->master_db->query("update `resumes_extras` set `compress`='$compress' where id={$r['resume_id']}");
        }
        Log::writelog("end update icdc_{$this->swoole->worker_id}.resumes_extras ...");
    }
}
