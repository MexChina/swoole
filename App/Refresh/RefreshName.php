<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/2/23
 * Time: 10:29
 */


namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Worker;
class RefreshName extends \Swoole\Core\App\Controller{
    private $db;
    private $page_size = 1000;
    private $error_txt;

    public function init(){
        //$this->db = $this->db("slave_icdc_".$this->swoole->worker_id);
        $this->db = $this->db("icdc_".$this->swoole->worker_id);
        $this->error_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/error.txt";
    }


    public function index(){
        $this->init();
        $result = $this->db->query("select count(1) as `total` from resumes")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh.......");

        System::exec_time();
        for($page=1;$page<=$page_total;$page++){
            $start_time = number_format(microtime(true), 8, '.', '');
            $start_memory = memory_get_usage();
            // $resume_ids=[];
            $result = $this->db->query("SELECT id FROM `resumes` WHERE id >= (SELECT id FROM `resumes` ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                $this->anonymous($r['id']);
                // $resume_ids[]=$r['id'];
            }

            // if (empty($resume_ids)) continue;

            // $work = new Worker("icdc_basic");
            // $gearman_return = $work->client([
            //     'c'=>'shell/Logic_name',
            //     'm'=>'index',
            //     'p'=>[ 'ids' => $resume_ids],
            // ],true,true);


            $msg = json_encode($gearman_return,JSON_UNESCAPED_UNICODE);
            if(!isset($gearman_return['err_no']) || $gearman_return['err_no'] != 0){
                $data = json_encode($resume_ids,JSON_UNESCAPED_UNICODE);
                error_log("$page\t$data\t $msg\n",3,$this->error_txt);
            }

            $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $str   = "{$runtime}s,";
            $memory_use = number_format((memory_get_usage() - $start_memory) / 1024, 2);
            $str .= "{$memory_use}kb";
            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total},$msg,$str");
        }
        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }

    /**
     * 匿名问题：
     * 主表中的name为 “匿名”   压缩包中的  name 为 “匿名”    联系方式中的name 不为“匿名”
     *
     * 修改方案：
     *   resumes.name
     *   contacts.name
     *   resumes_extra.basic.name
     *   resumes_extra.contact.name
     *
     * 四个进行比较，如果
     *
     * 名字优先级   basic.name > compress.basic.name > contact.name > compress.contact.name
     * 
     * @param  [type] $ids [description]
     * @return [type]      [description]
     */
    public function anonymous($id){
        $update_resumes = false;
        $update_resumes_extra = false;
        $update_contacts = false;

        $resumes_info = $this->db->query("select contact_id,name from resumes where id='$id'")->fetch();
        //如果主表不存在，停止执行
        if(empty($resumes_info)){
            error_log("resumes: $id not exists\n",3,SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/empty_ids.log");
            return;
        }
        
        //检查主表name
        if(empty($resumes_info['name']) || $resumes_info['name'] == '匿名'){
            $name = '';
            $update_resumes = true;
        }else{
            $name = $resumes_info['name'];
        }

        $contact_id = empty($resumes_info['contact_id']) ? 0 : $resumes_info['contact_id'];


        $resumes_extra_info = $this->db->query("select * from resumes_extras where id='$id'")->fetch();
        $resumes_extras_info['compress'] = json_decode(gzuncompress($resumes_extra['compress']), true);

        //检查信息表name
        if(empty($resumes_extras_info['compress']['basic']['name']) || $resumes_extras_info['compress']['basic']['name'] == '匿名'){
            $update_resumes_extra = true;
        }else{
            $name = empty($name) ? $resumes_extras_info['compress']['basic']['name'] : $name;
        }

        if(empty($contact_id)){
            if(!empty($resumes_extras_info['compress']['basic']['contact_id'])){
                $contact_id = $resumes_extras_info['compress']['basic']['contact_id'];
            }
        }

        $contacts_info = $this->db->query("select * from contacts where id='$contact_id'")->fetch();
        
        
        
    }

}