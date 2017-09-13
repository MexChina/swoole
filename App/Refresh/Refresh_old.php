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
use Swoole\App\Algorithm\Api;
class Refresh extends \Swoole\Core\App\Controller{
    private $db;
    private $page_size = 100;
    private $field=array(   //要刷库的字段
        //'cv_trade',
        'cv_tag',
        //'cv_entity',
        //'cv_education',     //cv_education,cv_degree

    );
    
    private $send_data;     //存放算法识别后的结果
    
    private $empty_txt;
    private $timeout_txt;
    private $error_txt;
    private $sql_txt;

    public function init(){
        $this->db = $this->db("master_icdc_".$this->swoole->worker_id);
        $this->empty_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/empty.txt";
        $this->timeout_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/timeout.txt";
        $this->error_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/error.txt";
        $this->sql_txt = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/sql.txt";
        $this->history_csv = SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/".date('Y_m_d')."_history_".$this->swoole->worker_id.".csv";
    }


    public function index(){
        $this->init();
        $api = new Api();
        $result = $this->db->query("select count(1) as `total` from resumes")->fetch();
        $page_total = ceil($result['total'] / $this->page_size);
        Log::write_log("icdc_{$this->swoole->worker_id} have {$page_total} to refresh.......");

        System::exec_time();
        for($page=1;$page<=$page_total;$page++){
	        $start_time = number_format(microtime(true), 8, '.', '');
            $start_memory = memory_get_usage();           
            $resume_ids=[];
            $result = $this->db->query("SELECT * FROM `resumes` WHERE id >= (SELECT id FROM `resumes` ORDER BY id asc LIMIT " . ($page - 1) * $this->page_size . ", 1) ORDER BY id asc LIMIT $this->page_size")->fetchall();

            foreach ($result as $r) {
                $resume_ids[]=$r['id'];
            }
            
            if (empty($resume_ids)) continue;
            
            $ids = implode(',',$resume_ids);
            $res = $this->db->query("select * from resumes_extras where id in($ids)")->fetchall();
            $i=0;
            $sql='';
            $this->db->autocommit(FALSE);
            foreach($res as $r){
                $send_data = array();
                $resume_id = $r['id'];
                $compress = json_decode(gzuncompress($r['compress']), true);

                //cv_education  cv_degree
                if(in_array('cv_education',$this->field)){
                    $education = $api->cv_education($compress);
                    if(!empty($education)){
                        $send_data['cv_education'] = json_encode($education['cv_education'],JSON_UNESCAPED_UNICODE);
                        $send_data['cv_degree'] = $education['cv_degree'];
                    }
                }

                //cv_trade
                if(in_array('cv_trade',$this->field)){
                    $trade = $api->cv_trade($compress);
                    if(!empty($trade)){
                        $send_data['cv_trade'] = json_encode($trade,JSON_UNESCAPED_UNICODE);
                    }
                }


                //cv_entity
                if(in_array('cv_entity',$this->field)){
                    $entity = $api->cv_entity($compress);
                    if(!empty($entity)){
                        $send_data['cv_entity'] = json_encode($entity,JSON_UNESCAPED_UNICODE);
                    }
                }

                //cv_tag
                if(in_array('cv_tag',$this->field)){
                    $tag = $api->cv_tag($compress);
                    $tag = addslashes(json_encode($tag,JSON_UNESCAPED_UNICODE));
                    if(!empty($tag)){
                        $this->db->query("update resumes_algorithms set cv_tag='$tag' where id='$resume_id'");
                    }
                    $msg = date('Y-m-d H:i:s').",".$resume_id.",cv_tag,".$tag."\n";
                    error_log($msg,3,$this->history_csv);
                }

                
                
                /*
                $work = new Worker("icdc_refresh",true,true);
                $gearman_return = $work->client(array(
                    'c'=>'resumes/Logic_algorithm',
                    'm'=>'save',
                    'p'=>array(
                        'id'=>$resume_id,
                        'data'=>$send_data
                    )
                ));
                
                if($gearman_return['err_no']){
                    $msg = json_encode($gearman_return,JSON_UNESCAPED_UNICODE);
                    $data = json_encode($send_data,JSON_UNESCAPED_UNICODE);
                    error_log("{$resume_id}\t$data\t $msg\n",3,$this->sql_txt);
                }else{
                    $msg = date('Y-m-d H:i:s').",".$resume_id.",".json_encode($send_data,JSON_UNESCAPED_UNICODE)."\n";
                    error_log($msg,3,$this->history_csv);
                    $i++;
                }*/
            }
            $this->db->commit();
	        $runtime    = number_format(microtime(true), 8, '.', '') - $start_time;
            $str   = "{$runtime}s,";
            $memory_use = number_format((memory_get_usage() - $start_memory) / 1024, 2);
            $str .= "{$memory_use}kb";     
            Log::write_log("icdc_{$this->swoole->worker_id},{$page}/{$page_total},$str");
        }
        Log::write_log("icdc_{$this->swoole->worker_id} 刷库完成，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
    }
}
