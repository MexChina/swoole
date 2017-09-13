<?php

namespace Swoole\App\GetRelations\Logic;

use Swoole\Core\Log;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DataEtl
 *
 * @author root
 */
class Relations {

    private $task_cache = [];
    private $response_cache = [];
    private $controller;

    function __construct($controller) {
        $this->controller = $controller;
    }

    public function index($params) {
        $start_time = microtime(true);
        //获取参数
        $request = $params['request'];
        $header = $params['header'];
        $company_ids = $request['company_ids'];
        $resume_ids = $request['resume_ids'];
        $get_data_from_mysql = $params['request']['get_data_from_mysql'] ?? false;
        $fd = intval($params['fd']);
        $header = $params['header'] ?? array();
        $hash_code = intval($header['hash_code']);
        $this->task_cache[$fd][$hash_code] = 0;
        $this->response_cache[$fd][$hash_code] = [];
        //分发处理
        Log::write_log("client_{$fd}_{$header['hash_code']}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] start process ......");
        //Log::write_log("client_{$fd}_{$header['hash_code']}:" . var_export($request, true));
        if (empty($company_ids)) {
            $rs = $this->controller->response("", $fd, 10002, "company_ids is empty ......");
            unset($this->task_cache[$fd]);
            unset($this->response_cache[$fd]);
            Log::write_log("client_{$fd}_{$header['hash_code']}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] company_ids is empty ******");
            Log::write_log("client_{$fd}_{$header['hash_code']}:" . var_export($request, true));
            return;
        }

        if (empty($resume_ids)) {
            $rs = $this->controller->response("", $fd, 10002, "resume_ids is empty ......");
            unset($this->task_cache[$fd]);
            unset($this->response_cache[$fd]);
            Log::write_log("client_{$fd}_{$header['hash_code']}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] resume_ids is empty ******");
            Log::write_log("client_{$fd}_{$header['hash_code']}:" . var_export($request, true));
            return;
        }
        if (count($company_ids) <= count($resume_ids)) {
            foreach ($company_ids as $company_id) {
                $data['company_id'] = intval(trim($company_id));
                if (!$company_id) {
                    continue;
                }
                $data['resume_ids'] = $resume_ids;
                $data['get_data_from_mysql'] = $get_data_from_mysql;
                $data['header'] = $header;
                $task_function = "get_relations_from_redis";
                $this->send_to_task($data, $task_function, $fd, $header, $hash_code, $start_time);
                $this->task_cache[$fd][$hash_code] ++;
            }
        } else {
            foreach ($resume_ids as $resume_id) {
                $data['resume_id'] = intval(trim($resume_id));
                if (!$resume_id) {
                    continue;
                }
                $data['company_ids'] = $company_ids;
                $data['get_data_from_mysql'] = $get_data_from_mysql;
                $data['header'] = $header;
                $task_function = "get_relations_from_redis2";
                $this->send_to_task($data, $task_function, $fd, $header, $hash_code, $start_time);
                $this->task_cache[$fd][$hash_code] ++;
            }
        }
    }

//发送到task进行处理
    private function send_to_task($data, $task_function, $fd, $header, $hash_code, $start_time) {
        $rs = $this->controller->task($data, function($data) use ($fd, $header, $hash_code, $start_time) {
            //var_export($data);
            if ($data != "empty") {
                $this->response_cache[$fd][$hash_code] = array_merge_recursive($data, $this->response_cache[$fd][$hash_code]);
            }
            $response_data = [];
            $count = 0;
            foreach ($this->response_cache[$fd][$hash_code] as $resume_id => $data) {
                $resume_id = trim($resume_id);
                foreach ($data as $re_resume_id => $value) {
                    //这里主要判断重复的人脉对，用来记录处理需要删除的人脉对
                    if (count($value['resume_id']) > 1) {
                        foreach ($value as $k => $v) {
                            $tmp[$k] = $v[0];
                        }
                        $this->controller->task($value, null, 'del_duplicate_data');
                        $value = $tmp;
                    }
                    //var_export($value);
                    $re_resume_id = trim($re_resume_id);
                    $response_data[$resume_id][$re_resume_id] = $value;
                    $count ++;
                }
            }
            Log::write_log("client_{$fd}_{$header['hash_code']}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] success get {$count} datas ......");
            //var_export($response_data);
            $this->task_cache[$fd][$hash_code] --;
            //echo PHP_EOL, "task_num:" . $this->task_cache[$fd], PHP_EOL;
            if ($this->task_cache[$fd][$hash_code] < 1) {
                unset($this->response_cache[$fd]);
                unset($this->task_cache[$fd]);
                $rs = $this->controller->response($response_data, $fd);
//                    if (empty($response_data)) {
//                        Log::write_log("client_{$fd}_{$header['hash_code']}:" . var_export($request, true));
//                    }
                $use_time = intval((microtime(true) - $start_time) * 1000);
                Log::write_log("client_{$fd}_{$header['hash_code']}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] response_cache:" .
                        count($this->response_cache) . ",task_cache:" . count($this->task_cache) . ",reponse {$count} datas use {$use_time} ms, memory use " .
                        \Swoole\Core\Helper\System::get_used_memory());
            }
            unset($header);
        }, $task_function);
    }

    function client_close($fd) {
        Log::write_log("client_{$fd} clean complated ......");
    }

}
