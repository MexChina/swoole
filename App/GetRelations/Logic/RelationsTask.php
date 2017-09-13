<?php

namespace Swoole\App\GetRelations\Logic;

use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\RedisClus;

/**
 * 统计异步任务脚本
 *
 * @author xuelin.zhou
 */
class RelationsTask {

    private $db_configs;
    private $redis;
    private $controller;
    private $fields = [];

    function __construct($controller) {
        $this->controller = $controller;
        $this->init();
    }

    public function init() {
        $this->db_configs = $this->controller->config->get("relations");
        $mysql = $this->controller->db($this->db_configs['mysql']);
        //获取字段列表
        $redis_key_table = $this->db_configs["redis"]["key"];
        foreach ($redis_key_table as $tablename => $key_ids) {
            $fields = [];
            $get_field_sql = " select COLUMN_NAME from information_schema.COLUMNS where table_name = '{$tablename}' AND table_schema = '{$this->db_configs['mysql']['name']}'";
            $rs = $mysql->query($get_field_sql)->fetchall();
            if ($rs) {
                foreach ($rs as $rs_v) {
                    if ($rs_v['COLUMN_NAME'] != 'updated_at') {
                        $fields[] = $rs_v['COLUMN_NAME'];
                    }
                }
            } else {
                Log::writelog("$tablename get field failed ......");
            }
            $this->fields[$tablename] = $fields;
        }
        $mysql->close();
        $this->redis = new RedisClus($this->db_configs['redis']['hosts']);
        $conn_rs = $this->redis->connect();
        if (!$conn_rs) {
            Log::writelog("connect redis failed ******");
            $this->redis = null;
        }
    }

    //获取人脉关系数据(company_id, resume_ids)
    public function get_relations_from_redis($params) {
        $company_id = intval($params['company_id']);
        $resume_ids = $params['resume_ids'];

        $header = $params['header'] ?? array();
        System::exec_time();
        if (!empty($params['get_data_from_mysql'])) {
            Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] start get data from mysql ......");
            $mysql = $this->controller->db($this->db_configs['mysql']);
            foreach ($this->fields as $table_name => $fields) {
                $sql = "select " . implode(",", $fields) . " from {$table_name} where " .
                        (empty($resume_ids) ? "" : "resume_id in(" . implode(",", $resume_ids) . ") and ") .
                        " `re_cur_company_id` = '$company_id'";
                Log::write_log("get data sql:$sql ......");
                $rsdata = $mysql->query($sql)->fetchall();
                //Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] get data from mysql " . count($rsdata) . " datas......");
                if (!empty($rsdata)) {
                    $reids_datas = [];
                    foreach ($rsdata as $data) {
                        $reids_datas[] = DumpData::format_redis_values(implode(",", $data), $table_name, $this->db_configs);
                    }
                    if (!empty($reids_datas)) {
                        $rs = $this->redis->hmset($reids_datas);
                    }
                    if (is_array($rs)) {
                        Log::writelog("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] write failed list " . implode(",", $rs) . " ******");
                    } else {
                        Log::writelog("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] write success " . count($reids_datas) . " datas to redis ......");
                    }
                } else {
                    Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] get data from mysql is empty ......");
                }
            }
        }
        $keys = $return = [];
        foreach ($resume_ids as $resume_id) {
            $resume_id = trim(intval($resume_id));
            if (!$resume_id) {
                continue;
            }
            $keys[] = $this->get_key($resume_id, $company_id);
        }
        //Log::write_log("keys " . var_export($keys, true) . " ......");
        $rs = $this->redis->hmget($keys);
        //Log::write_log("rs " . var_export($rs, true) . " ......");
        //Log::write_log(count($resume_ids) . " data get value use " . System::exec_time() . " ms ......");
        foreach ($rs as $key => $value) {
            if (!empty($value)) {
                $this->format_value($value, $return);
            }
        }
        //Log::write_log(count($resume_ids) . " data ..... use " . System::exec_time() . " ms ......");
        if (empty($return)) {
            $return = "empty";
            Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] get data from redis is empty ......");
        } else {
            Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] get " . count($return) . " datas from redis， memory use " . \Swoole\Core\Helper\System::get_used_memory() . " ......");
        }
        return $return;
    }

    //获取人脉关系数据(company_ids, resume_id)
    public function get_relations_from_redis2($params) {
        $company_ids = $params['company_ids'];
        $resume_id = intval($params['resume_id']);

        $header = $params['header'] ?? array();
        System::exec_time();
        if (!empty($params['get_data_from_mysql'])) {
            Log::write_log("resume_id:{$resume_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] start get data from mysql ......");
            $mysql = $this->controller->db($this->db_configs['mysql']);
            foreach ($this->fields as $table_name => $fields) {
                $sql = "select " . implode(",", $fields) . " from {$table_name} where " .
                        (empty($resume_id) ? "" : "resume_id = '{$resume_id}' and ") .
                        " `re_cur_company_id` in (" . implode(",", $company_ids) . ")";
                Log::write_log("get data sql:$sql ......");
                $rsdata = $mysql->query($sql)->fetchall();
                //Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] get data from mysql " . count($rsdata) . " datas......");
                if (!empty($rsdata)) {
                    $reids_datas = [];
                    foreach ($rsdata as $data) {
                        $reids_datas[] = DumpData::format_redis_values(implode(",", $data), $table_name, $this->db_configs);
                    }
                    if (!empty($reids_datas)) {
                        $rs = $this->redis->hmset($reids_datas);
                    }
                    if (is_array($rs)) {
                        Log::writelog("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] write failed list " . implode(",", $rs) . " ******");
                    } else {
                        Log::writelog("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] write success " . count($reids_datas) . " datas to redis ......");
                    }
                } else {
                    Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] get data from mysql is empty ......");
                }
            }
        }
        $keys = $return = [];
        foreach ($company_ids as $company_id) {
            $company_id = trim(intval($company_id));
            if (!$company_id) {
                continue;
            }
            $keys[] = $this->get_key($resume_id, $company_id);
        }
        //Log::write_log("keys " . var_export($keys, true) . " ......");
        $rs = $this->redis->hmget($keys);
        //Log::write_log("rs " . var_export($rs, true) . " ......");
        //Log::write_log(count($resume_ids) . " data get value use " . System::exec_time() . " ms ......");
        foreach ($rs as $key => $value) {
            if (!empty($value)) {
                $this->format_value($value, $return);
            }
        }
        //Log::write_log(count($resume_ids) . " data ..... use " . System::exec_time() . " ms ......");
        if (empty($return)) {
            $return = "empty";
            Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] get data from redis is empty ......");
        } else {
            Log::write_log("company_id:{$company_id}[{$header['local_ip']}:{$header['product_name']}:{$header['log_id']}] get " . count($return) . " datas from redis， memory use " . \Swoole\Core\Helper\System::get_used_memory() . " ......");
        }
        return $return;
    }

    //删除redis里面的重复数据
    public function del_duplicate_data($params) {
        $resume_id = $params['resume_id'][0];
        $re_resume_id = $params['re_resume_id'][0];
        $re_cur_company_ids = $params['re_cur_company_id'];
        $is_colleague_relation = empty($params['school_id']);
        $mysql = $this->controller->db($this->db_configs['mysql']);
        $table_name = $is_colleague_relation ? "colleague_relations" : "schoolmate_relations"; //确认人脉类型
        if ($is_colleague_relation) {
            //查找数据库重该人脉的确切公司id
            $sql = "SELECT re_cur_company_id FROM $table_name WHERE resume_id = '{$resume_id}' AND re_resume_id='{$re_resume_id}'";
            $re_cur_company_id = $mysql->query($sql)->fetchall()[0]['re_cur_company_id'];
            unset($re_cur_company_ids[array_search($re_cur_company_id, $re_cur_company_ids)]);
        }
        //删除不存在的公司ID的redis key
        foreach ($re_cur_company_ids as $re_cur_company_id) {
            $key = "{$this->db_configs['redis']['key_pre']}{$resume_id}:{$re_cur_company_id}"; //要删除的redis key
            $field = "{$re_resume_id}:" . $this->db_configs['redis']['value_pre'][$table_name]; //要删除的redis field
            $rs = $this->redis->hdel($key, $field);
            if ($rs) {
                Log::write_log("delete key:$key, field:$field success ......");
            } else {
                Log::write_log("delete key:$key, field:$field failed ******");
            }
        }
        $mysql->close();
    }

    private function get_key($resume_id, $company_id) {
        $data = [];
        $key_pre = $this->db_configs['redis']['key_pre'] ?? "";
        $redis_key = "{$key_pre}{$resume_id}:{$company_id}";
        return $redis_key;
    }

    private function format_value($value, &$return) {
        foreach ($value as $data) {
            foreach ($this->db_configs["redis"]["value_pre"] as $tablename => $pre) {
                if (strpos($data, $pre) === 0) {
                    $data = explode($this->db_configs["pg"]["delimiter"], trim($data, $pre));
                    $data = array_combine($this->fields[$tablename], $data);
                    if (!empty($data['resume_id']) && !empty($data['re_resume_id'])) {
                        $return[" {$data['resume_id']} "][" {$data['re_resume_id']} "] = $data;
                    }
                    break;
                }
            }
        }
    }

}
