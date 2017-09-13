<?php
namespace Swoole\App\Department;

use Swoole\Core\App\Controller;
use Swoole\Core\AppServer;
use Swoole\Core\Log;
use Swoole\Core\Helper\Strings;
use Swoole\Core\Lib\Worker;
class Department extends Controller{


    public function init(){}

    public function index(){
        $gmworker = new Worker('api_department');
        $gmworker->worker(function($data){
            $result = array();//存放返回结果集
            $task_params = array();//子任务参数集
            $department_table = AppServer::$tables['department'];
            $i=0;
            if(empty($data['work_map'])){
                Log::write_log("参数为空直接返回...");
                $result = array("1"=>array("result_id"=>"0"));
            }else{
                foreach($data['work_map'] as $w){
                    if(empty($w['title'])){
                        Log::write_log("参数为空直接返回...");
                        $result = array("{$w['id']}"=>array("result_id"=>"0"));
                    }else{
                        $key = $this->department($w['title']);
                        if(strlen($key) > 32){
                            $result["{$w['id']}"] = array('result_id'=>"0");
                            continue;
                        }
                        $key_value = $department_table->get($key);
                        if($key_value){
                            Log::write_log("从内存中获取成功...");
                            $result["{$w['id']}"] = array('result_id'=>"{$key_value['id']}");
                        }else{
                            Log::write_log("开始写入内存...");
                            global $ATOMIC;
                            $ATOMIC->add();
                            $new_id = $ATOMIC->get();
                            $department_table->set($key,array('id'=>$new_id));
                            $result["{$w['id']}"] = array("result_id"=>"{$new_id}");
                            $task_params[$i] = array('id'=>$new_id,'name'=>$key);
                        }
                    }
                    $i++;
                }
            }

            if($task_params){
                $this->task($task_params);
            }
            return array('results'=>$result);
        });
    }


    public function department($key){
        $key = Strings::filter($key);   //基础过滤

        return $key;
    }

}