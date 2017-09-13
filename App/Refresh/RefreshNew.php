<?php
/**
 * Created by PhpStorm.
 * User: ifchangebisjq
 * Date: 2017/1/6
 * Time: 14:31
 */

namespace Swoole\App\Refresh;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Worker;
class Refresh extends \Swoole\Core\App\Controller{
    private $db;
    private $page_size = 1000;
    private $refresh_at = '2017-02-21 00:00:00';
    private $field=array(   //要刷库的字段
        'cv_trade',
        'cv_entity',
        'cv_tag',
    );

    private $send_data;     //存放算法识别后的结果

    private $empty_txt;
    private $timeout_txt;
    private $error_txt;
    private $sql_txt;

    public function init(){
        //$this->db = $this->db("slave_icdc_".$this->swoole->worker_id);
        $this->db = $this->db("icdc_".$this->swoole->worker_id);
        $this->empty_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/empty.txt";
        $this->timeout_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/timeout.txt";
        $this->error_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/error.txt";
        $this->sql_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/sql.txt";

    }


    public function index(){
        $this->init();
        $result = $this->db->query("select count(1) as `total` from resumes_flags")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh.......");

        System::exec_time();
        for($page=1;$page<=$page_total;$page++){
            $start_time = number_format(microtime(true), 8, '.', '');
            $start_memory = memory_get_usage();
            $resume_ids=[];
            $result = $this->db->query("SELECT * FROM `resumes_flags` WHERE id >= (SELECT id FROM `resumes_flags` ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                if(strcmp($this->refresh_at, $r['refresh_at']) > 0){
                    $resume_ids[]=$r['id'];
                }
            }

            if (empty($resume_ids)) continue;

            $ids = implode(',',$resume_ids);
            $res = $this->db->query("select * from resumes_extras where id in($ids)")->fetchall();
            $i=0;
            foreach($res as $r){
                $this->send_data = array();
                $resume_id = $r['id'];
                $compress = json_decode(gzuncompress($r['compress']), true);
                foreach($this->field as $field){
                    $this->$field($resume_id,$compress);
                }

                if(empty($this->send_data)) continue;

                $work = new Worker("icdc_basic");
                $gearman_return = $work->client(array(
                    'c'=>'resumes/Logic_algorithm',
                    'm'=>'save',
                    'p'=>array(
                        'id'=>$resume_id,
                        'data'=>$this->send_data
                    ),
                ),true,true);

                if($gearman_return['err_no']){
                    $msg = json_encode($gearman_return,JSON_UNESCAPED_UNICODE);
                    $data = json_encode($this->send_data,JSON_UNESCAPED_UNICODE);
                    error_log("{$resume_id}\t$data\t $msg\n",3,$this->sql_txt);
                }else{
                    $i++;
                }
            }

            $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $str   = "{$runtime}s,";
            $memory_use = number_format((memory_get_usage() - $start_memory) / 1024, 2);
            $str .= "{$memory_use}kb";
            Log::write_log("icdc_{$this->swoole->worker_id},$i,{$page}/{$page_total},$str");
        }
        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }

    /**
     * 思南接口
     */
    public function cv_trade($resume_id,$compress){
        if(empty($compress['work'])){
            error_log("{$resume_id} \t cv_trade work empty\n",3,$this->empty_txt);
            return '';
        }

        $input['cv_id'] = uniqid('cv_trade_');
        $input['work_list'] = array();
        foreach($compress['work'] as $work) {
            $work_id = $work['id'];
            if(empty($work['corporation_name'])) {
                continue;
            }

            $input['work_list'][] = array(
                'position' => empty($work['position_name']) ? '' : $work['position_name'],
                'company_name' => empty($work['corporation_name']) ? '' : $work['corporation_name'],
                'work_id' => intval($work_id),
                'desc' => empty($work['corporation_desc']) ? (empty($work['responsibilities']) ? '' : $work['responsibilities']) : $work['corporation_desc'],
                'industry_name' => empty($work['industry_name']) ? '' : $work['industry_name'],
            );
        }
        if(empty($input['work_list'])) {
            error_log("{$resume_id} \t cv_tag_work_list empty\n",3,$this->empty_txt);
            return '';
        }

        $work = new Worker("corp_tag");
        $rs = $work->client($input,true);
        if(isset($rs['status']) && $rs['status']==0){
            $this->send_data['cv_trade'] = json_encode($rs['result']);
            return '';
        }
        $msg = "cv_trade\t" . $resume_id."\t".json_encode($rs,JSON_UNESCAPED_UNICODE) . "\n";
        error_log($msg,3,$this->error_txt);
    }

    /*
     * 刘贝接口
     */
    public function cv_entity($resume_id,$compress){
        if(empty($compress['work'])) {
            error_log("{$resume_id} \t cv_entity work empty\n",3,$this->empty_txt);
            return '';
        }

        $work_list = array();
        $cv_entity = array();
        foreach ($compress['work'] as $work) {
            $work_id = $work['id'];
            if (empty($work['position_name']) && empty($work['responsibilities'])) {
                $cv_entity[$work_id]['no_entity'] = '';
                continue;
            }

            $work_list[$work_id] = array(
                'id'    => $work_id,
                'type'  => 0, // 0表示简历,未来可能增加职位等类型
                'title' => empty($work['position_name']) ? '' : $work['position_name'],
                'desc'  => empty($work['responsibilities']) ? '' : $work['responsibilities']
            );
        }

        if(empty($work_list)) {
            error_log("{$resume_id} \t cv_entity_work_list empty\n",3,$this->empty_txt);
            return '';
        }

        $input = array(
            'c' => 'cv_entity',
            'm' => 'get_cv_entitys',
            'p' => array(
                'cv_id' => uniqid('cv_entity_'),
                'work_map' => $work_list
            ),
        );

        $work = new Worker("cv_entity_new_format");
        $rs = $work->client($input,true);

        if(isset($rs['err_no']) && $rs['err_no']==0){
            if (! empty($rs['results'])) {
                foreach ($rs['results'] as $work_id => $row) {
                    $cv_entity[$work_id] = $row;
                }
            }
            $this->send_data['cv_entity'] = json_encode($cv_entity);
            return '';
        }
        $msg = "cv_entity\t" . $resume_id."\t".json_encode($rs,JSON_UNESCAPED_UNICODE) . "\n";
        error_log($msg,3,$this->error_txt);
    }

    /*
     * 刘贝接口
     */
    public function cv_tag($resume_id,$compress){
        if(empty($compress['work'])) {
            error_log("{$resume_id} \t cv_tag work empty\n",3,$this->empty_txt);
            return '';
        }

        $work_list = array();
        $cv_tag = array();
        foreach ($compress['work'] as $work) {
            $work_id = $work['id'];
            if (empty($work['position_name']) && empty($work['responsibilities'])) {
                $cv_tag[$work_id]['no_tag'] = '';
                continue;
            }

            $work_list[$work_id] = array(
                'id'    => $work_id,
                'type'  => 0, // 0表示简历,未来可能增加职位等类型
                'title' => empty($work['position_name']) ? '' : $work['position_name'],
                'desc'  => empty($work['responsibilities']) ? '' : $work['responsibilities']
            );
        }

        if(empty($work_list)) {
            error_log("{$resume_id} \t cv_tag_work_list empty\n",3,$this->empty_txt);
            return '';
        }

        $input = array(
            'c' => 'cv_tag',
            'm' => 'get_cv_tags',
            'p' => array(
                'cv_id' => uniqid('cv_entity_'),
                'work_map' => $work_list
            ),
        );

        $work = new Worker("tag_predict");
        $rs = $work->client($input,true);

        if(isset($rs['err_no']) && $rs['err_no']==0){
            if (! empty($rs['results'])) {
                foreach ($rs['results'] as $work_id => $row) {
                    $cv_tag[$work_id] = $row;
                }
            }
            $this->send_data['cv_tag'] = json_encode($cv_tag);
            return '';
        }
        $msg = "cv_tag\t" . $resume_id."\t".json_encode($rs,JSON_UNESCAPED_UNICODE) . "\n";
        error_log($msg,3,$this->error_txt);
    }

}