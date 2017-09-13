<?php

namespace Swoole\App\GetRelations\Logic;

use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\RedisClus;

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
class DumpData {

    private $controller;
    private $per_save_num = 1000; //每次往task发送的条数
    private $per_tasker_num = 12; //每个进程占用的task的数量,同时也是导出GP时同时执行的数量
    private $tasker_num = 0; //save 时发送到task的计数
    private $dump_tasker_num = 0; //从GP导出时发送到task的计数
    private $count_line = 0; //当前进程需要处理的总行数
    private $send_worker_num = 0; //分发worker进程数量
    private $return_worker_num = 0; //处理完毕的worker进程数量
    private $do_count = 0; //处理的行数
    private $send_task_data = []; //导出GP的task数据缓存
    private $send_other_worker_data = []; //缓存发往其他worker进程处理的数据,主要是在所有进程都繁忙的状态下缓存需要发送的数据，等待空闲
    private $free_other_worker_ids = []; //正在执行的进程list，等待执行完毕，清理或者重新执行
    private $dump_pg_data_cache = []; //导出时参数缓存
    private $total_count = 0; //导出总行数
    private $start_time = 0;
    private $dump_fd = null; //执行导出的fd，只能有一个
    private $is_saveto_redis = false;
    private $mysql_conn; //mysql 连接
    private $db_configs = [];

    function __construct($controller) {
        $this->controller = $controller;
    }

    public function init_config($params) {
        $config_name = $params['config_name'];
        $fd = intval($params['fd']);
        $this->dump_fd = $fd;
        if ($config_name) {
            //Log::write_log("db_configs:".var_export($this->db_configs,true));
            $this->db_configs = $this->controller->reload_config($config_name); //重新加载最新的配置文件
            //$this->db_configs = $this->controller->config->get($config_name);
            //$this->db_configs['relations'] = $relations_config;
            //Log::write_log("db_configs:".var_export($this->db_configs,true));
            $this->mysql_conn = $this->controller->db($this->db_configs['mysql']);
            $this->start_time = microtime(true);
            $this->per_tasker_num = !empty($this->db_configs['pg']['task_worker_num']) ? intval($this->db_configs['pg']['task_worker_num']) : $this->per_tasker_num;
            Log::writelog("init $config_name config success ......");
        } else {
            Log::writelog("init $config_name config failed ......");
        }
    }

    /*
     * 导出数据
     */

    public function dump_main($params) {
        //获取当前mysql 库下面所有的表名
        $fd = intval($params['fd']);
        if (null === $this->dump_fd) {
            $this->dump_fd = $fd;
        }
        $config_name = $params['config_name'] ?? "relations";
        //初始化task配置
        $this->controller->task_all(["config_name" => $config_name], "init_config");
        $this->init_config(["config_name" => $config_name, "fd" => $fd]);
        $table_name = $params['tablename'] ?? ""; //需要导出的表明，如果不指定则导出所有表
        $sub_table_ids = isset($params['sub_table_id']) ?
                (is_array($params['sub_table_id']) ? $params['sub_table_id'] : [$params['sub_table_id']]) : null; //需要导出的分库ID，不指定则全部导出
        $is_dump_from_gp = $params['is_dump_from_gp'] ?? TRUE; //是否需要重GP导出，如果不导出则直接读取上次导出的文件
        $is_saveto_redis = !empty($this->db_configs['redis']);
        $dump_talbes = empty($table_name) ? $this->db_configs['mysql']['dump_tables'] : array($table_name);
        $rs_table = $this->mysql_conn->query("show tables")->fetchall();
        foreach ($rs_table as $v) {
            $fields = $sub_table_config = $matches = [];
            $tablename = $v["Tables_in_{$this->db_configs['mysql']['name']}"];
            if (empty(trim($tablename)) || (!empty($dump_talbes) && !in_array($tablename, $dump_talbes))) {
                continue;
            }
            //获取字段列表
            $fields = $this->get_mysql_table_fields($tablename);
            if (empty($fields)) {
                $this->controller->response("", $this->dump_fd, 0002, "get $tablename field error ......");
                $this->dump_fd = null;
                return;
            }
            $fields = implode(",", $fields);
            $save_sql = "insert into $tablename ($fields) values ";
            //获取表的分布算法和分库地址
            $get_create_sql = "show create table {$tablename}";
            $table_struct = @$this->mysql_conn->query($get_create_sql)->fetchall()[0]['Create Table'];
            preg_match_all("/COMMENT = \'srv \"(.+)_(\d+)\"\'/", $table_struct, $matches);
            if (empty($matches[1])) {
                $this->per_save_num = $this->per_save_num * 4;
                $save_sql = "insert into $tablename ($fields) values ";
                $sub_table_config['Host'] = $this->db_configs['mysql']['host'];
                $sub_table_config['Db'] = $this->db_configs['mysql']['name'];
                $sub_table_config['Port'] = $this->db_configs['mysql']['port'];
                $sub_table_config['Username'] = $this->db_configs['mysql']['user'];
                $sub_table_config['Password'] = $this->db_configs['mysql']['passwd'];
                $sub_table_config['Server_name'] = $tablename;
                $sub_table_config['tablename'] = $tablename;
                $sub_table_config['sql'] = $save_sql; //写入sql
                $sub_table_config['count'] = 0; //写入条数
                $sub_table_config['sub_table_id'] = "null"; //分库ID, 这里没有，则统一标示为‘null’
                $sub_table_config['field_names'] = $fields;
                $sub_table_config['is_dump_from_gp'] = $is_dump_from_gp;
                $sub_table_config['is_saveto_redis'] = $is_saveto_redis;
                $sub_table_config['config_name'] = $config_name;
                $this->send_task_data[$tablename] = 1;
                $this->dump_data_from_gp($sub_table_config);
            } else {
                foreach ($matches[1] as $k => $tn) {
                    $tk = $matches[2][$k];
                    if (null !== $sub_table_ids && !in_array($tk, $sub_table_ids)) {//指定导出分库的ID
                        continue;
                    }
                    $sub_table_name = "{$tn}_{$tk}";
                    $rr = $this->mysql_conn->query("select * from mysql.servers where Server_name = '$sub_table_name'")->fetchall();
                    $sub_table_config = $rr[0];
                    $sub_table_config['tablename'] = $tablename;
                    $sub_table_config['sql'] = $save_sql; //写入sql
                    $sub_table_config['count'] = 0; //写入条数
                    $sub_table_config['sub_table_id'] = $tk; //分库ID
                    $sub_table_config['field_names'] = $fields;
                    $sub_table_config['is_dump_from_gp'] = $is_dump_from_gp;
                    $sub_table_config['is_saveto_redis'] = $is_saveto_redis;
                    $sub_table_config['config_name'] = $config_name;
                    $sub_table_name = "{$tablename}_{$sub_table_config['sub_table_id']}";
                    $this->send_task_data[$sub_table_name] = 1;
                    $this->dump_data_from_gp($sub_table_config);
                    //$this->send_other_worker($sub_table_config);
                    //break;
                }
            }
        }
    }

    /*
     * 利用gpfidst导出gp数据到文件
     */

    private function dump_data_from_gp($params) {
        if (!isset($params['tablename']) || !isset($params['sub_table_id'])) {
            return;
        }
        $sub_table_name = ($params['sub_table_id'] === "null") ? $params['tablename'] : "{$params['tablename']}_{$params['sub_table_id']}";
        if ($this->dump_tasker_num >= $this->per_tasker_num / 2) {
            $this->dump_pg_data_cache[$sub_table_name] = $params;
        } else {
            $tasker = $this->controller->task($params, function($reponse_data, $params) {
                $this->dump_tasker_num --;
                $sub_table_name = ($params['sub_table_id'] === "null") ? $params['tablename'] : "{$params['tablename']}_{$params['sub_table_id']}";
                if (!empty($this->dump_pg_data_cache)) {
                    $this->dump_data_from_gp(array_shift($this->dump_pg_data_cache));
                }
                if (isset($this->send_task_data[$sub_table_name])) {
                    unset($this->send_task_data[$sub_table_name]);
                }
                Log::writelog("[$sub_table_name] dump task complated ,reponse_data:$reponse_data......");
                $params['file_path'] = $reponse_data;
                $this->send_other_worker_data[$sub_table_name] = $params;
                $this->send_other_worker($params);
            }, 'gpfdist');
            if ($tasker !== false) {
                $this->dump_tasker_num ++;
            }
        }
    }

    private function send_other_worker($data) {
        if (!isset($data['tablename']) || !isset($data['sub_table_id'])) {
            return;
        }
        $sub_table_name = ($data['sub_table_id'] === "null") ? $data['tablename'] : "{$data['tablename']}_{$data['sub_table_id']}";
        //异步worker进程的调用，除非显示地标记执行完毕，不能调用正在执行的worker_id
        if (empty($this->free_other_worker_ids) && $this->send_worker_num < 1) {
            $this->free_other_worker_ids = $this->controller->workers;
        }
        $send_worker_id = array_shift($this->free_other_worker_ids);
        if ($send_worker_id !== NULL) {
            $data['main_worker_id'] = $this->controller->swoole->worker_id;
            $r = $this->controller->send_to_other_worker($data, "save_send", function($res, $req, $work_id) use ($sub_table_name) {
                if ($res) {
                    Log::writelog("[$sub_table_name] start success......");
                } else {
                    Log::writelog("[$sub_table_name] start failed......");
                    $this->check_next_process($work_id);
                }
            }, $send_worker_id);
        } else {
            $r = false;
        }
        if ($r !== false) {
            if (isset($this->send_other_worker_data[$sub_table_name])) {
                unset($this->send_other_worker_data[$sub_table_name]);
            }
            $this->send_worker_num ++;
            //Log::writelog("[$sub_table_name] send success to worker_{$r},send_worker_num:{$this->send_worker_num} ......");
        }
    }

    /*
     * 异步回调
     */

    function main_back_call($data) {
        $sub_table_name = $data['sub_table_name'];
        $work_id = $data['work_id'];
        $this->total_count += intval($data['count']);
        Log::writelog("[$sub_table_name] worker_{$work_id} do complated ......");
        $this->check_next_process($work_id);
    }

    private function check_next_process($work_id) {
        $this->return_worker_num ++;
        array_push($this->free_other_worker_ids, $work_id);
        //Log::writelog("return_worker_num:{$this->return_worker_num}......" . var_dump($this->send_other_worker_data, TRUE) . var_dump($this->send_task_data, TRUE));
        if (!empty($this->send_other_worker_data)) {
            $this->send_other_worker(array_shift($this->send_other_worker_data));
        } elseif ($this->return_worker_num >= $this->send_worker_num &&
                empty($this->send_other_worker_data) &&
                empty($this->send_task_data)) {
            $end_time = microtime(true);
            $use_time = round(($end_time - $this->start_time));
            Log::writelog("************************************************* will shutdown server, total dump {$this->total_count} data use $use_time s *************************************************");
            //$this->swoole->shutdown();
            $this->controller->dump_fd = null;
            $this->controller->response("total dump {$this->total_count} data use $use_time s", $this->dump_fd);
        }
    }

    /*
     * 写入数据分发
     */

    function save_send($params, $call_back = false) {
        $sub_table_name = ($params['sub_table_id'] === "null") ? $params['tablename'] : "{$params['tablename']}_{$params['sub_table_id']}";
        $tablename = $params['tablename'];
        $sub_table_config = $params;
        $main_worker_id = $params['main_worker_id'];
        $is_saveto_redis = $params['is_saveto_redis'];
        if (!$call_back) {
            $file_path = $sub_table_config['file_path'];
            //var_dump($sub_table_config);
            $field_names = $sub_table_config['field_names'];
            if (!file_exists($file_path)) {
                Log::writelog("data file($file_path) not exists ......");
                //$this->send_to_other_worker(array('sub_table_name' => $sub_table_name, 'work_id' => $this->controller->workerid), "main_back_call", null, 0);
                return false;
            }
            Log::writelog("[$sub_table_name] start count file line %%%%%%%%%%%%%%%%%%%%%%%%%%%");
            $this->count_line = $this->count_line($file_path);
            Log::writelog("[$sub_table_name] $file_path total $this->count_line line %%%%%%%%%%%%%%%%%%%%%%%%%%%");
            $this->fileSp = new \SplFileObject($file_path);
            $this->do_count = 0;
            $this->tasker_num = 0;
            $table_key = $sub_table_config['sub_table_id']; //分库键
            //$this->fileSp->seek($server->worker_id * $this->per_file_number);
            //清空数据表
            $db_config = array(
                'host' => $params['Host'],
                'port' => $params['Port'],
                'user' => $params['Username'],
                'passwd' => $params['Password'],
                'name' => $params['Db'],
            );
            $mysql_conns = $this->controller->db($db_config);
            $tc_rs = $mysql_conns->query("truncate table {$params['tablename']}");
            if (!empty($tc_rs->result)) {
                Log::writelog("[$sub_table_name] truncate table `{$params['tablename']}` success *******");
            } else {
                Log::writelog("[$sub_table_name] truncate table `{$params['tablename']}` failed *******");
            }
            $mysql_conns->close();
            //clean redis db
            if (!empty($this->db_configs['redis'])) {
                $redis_client = new RedisClus($this->db_configs['redis']['hosts']);
                $conn_rs = $redis_client->connect();
                if (!$conn_rs) {
                    Log::writelog("connect redis failed ******");
                } else {
                    $redis_client->clean_db();
                }
                $redis_client->close();
            }

            if ($is_saveto_redis) {
                $this->write_field_names_to_redis($field_names, $tablename); //写入redis mysql的字段名列表，应该跟存储在redis里面的value具有相同的顺序和标示
            }
            System::exec_time();
        }
        $this_count = 0;
        $save_sql = $params['sql'];
        $redis_datas = [];
        while (!$this->fileSp->eof()) {
            $line = $this->fileSp->current();
            $this->fileSp->next();
            $this->do_count++;
            //$data = explode($this->db_configs['pg']['delimiter'], $line);
            //计算分布库
            if (!empty(trim($line))) {
                if ($this_count > 0) {
                    $sub_table_config['sql'] .= ",";
                }
                $this_count ++;
                $sub_table_config['sql'] .= "(" . $line . ")";
                if ($is_saveto_redis && !empty($this->db_configs['redis']['key'][$tablename])) {
                    $redis_datas[] = self::format_redis_values($line, $tablename, $this->db_configs);
                }
            }
            if ($this_count >= $this->per_save_num) {
                $sub_table_config['count'] = $params['count'] = $this->do_count;
                if ($is_saveto_redis) {
                    $sub_table_config['redis_fun'] = "hmset";
                    $sub_table_config['redis_datas'] = $redis_datas;
                }
                $tasker = $this->controller->task($sub_table_config, function($data) use ($params, $sub_table_name) {
                    $this->tasker_num --;
                    $main_worker_id = $params['main_worker_id'];
                    if ($this->do_count < $this->count_line) {
                        $this->save_send($params, true);
                    } elseif ($this->tasker_num < 1) {
                        Log::writelog("[$sub_table_name] dump data to mysql, use " . System::exec_time() . "ms ......");
                        $this->controller->send_to_other_worker(array('sub_table_name' => $sub_table_name, 'work_id' => $this->controller->workerid, 'count' => $this->do_count), "main_back_call"
                                , null, $main_worker_id);
                    }
                }, "dump_to_mysql");
                Log::writelog("[$sub_table_name] read $this->do_count line, complated " . round($this->do_count * 100 / $this->count_line) . "% memory use " . System::get_used_memory() . "......");
                if ($tasker !== false) {
                    $sub_table_config['sql'] = $save_sql;
                    $this_count = 0;
                    $this->tasker_num ++;
                    if ($this->tasker_num >= $this->per_tasker_num) {
                        break;
                    }
                } else {
                    Log::writelog("[$sub_table_name] send task failed *******");
                }
            }
        }
        if ($this_count > 0) {
            $sub_table_config['count'] = $params['count'] = $this->do_count;
            if ($this->is_saveto_redis) {
                $sub_table_config['redis_fun'] = "hmset";
                $sub_table_config['redis_datas'] = $redis_datas;
            }
            $tasker = $this->controller->task($sub_table_config, function($data) use ($sub_table_name, $main_worker_id) {
                $this->tasker_num --;
                if ($this->tasker_num < 1) {
                    Log::writelog("[$sub_table_name] dump data to mysql, use " . System::exec_time() . "ms ......");
                    $this->controller->send_to_other_worker(array('sub_table_name' => $sub_table_name, 'work_id' => $this->controller->workerid, 'count' => $this->do_count)
                            , "main_back_call", null, $main_worker_id);
                }
            }, "dump_to_mysql");
            if ($tasker !== false) {
                $this->tasker_num++;
            }
        }
        return TRUE;
    }

    function write_field_names_to_redis($field_names, $tablename) {
        if (empty($field_names)) {
            return false;
        }
        $redis = new RedisClus($this->db_configs['redis']['hosts']);
        $conn_rs = $redis->connect();
        $field_name_redis_key = $this->db_configs['redis']['filed_names_pre'] . $this->db_configs['redis']['value_pre'][$tablename];
        $field_name_redis_value = $field_names;
        if ($conn_rs) {
            $rs = $redis->set($field_name_redis_key, $field_name_redis_value);
            if (is_array($rs)) {
                Log::writelog("[tablename:$tablename]write field_name_redis list ******");
            }
        } else {
            Log::writelog("connect redis failed ******");
        }
        $redis->close();
        return TRUE;
    }

    public static function format_redis_values($line, $tablename, $configs) {
        $data = [];
        $key_pre = $configs['redis']['key_pre'] ?? "";
        $redis_key = $key_pre;
        $redis_field = "";
        $redis_fields = $configs['redis']['key'][$tablename];
        $r_data = explode($configs['pg']['delimiter'], $line);
        foreach ($redis_fields as $field_id) {
            $redis_key .= ($r_data[$field_id] . ":");
        }
        $value_pre = $configs['redis']['value_pre'][$tablename] ?? "";
        $redis_key = trim($redis_key, ":");
        if (!empty($redis_ffields = $configs['redis']['field'][$tablename])) {
            foreach ($redis_ffields as $field_id) {
                $redis_field .= ((is_numeric($field_id) ? $r_data[$field_id] : $field_id) . ":");
            }
            $redis_field = trim($redis_field, ":");
            $data = ["key" => $redis_key, "field" => $redis_field, "value" => $value_pre . trim($line, "\n")];
        } else {
            $data = [$redis_key => $value_pre . trim($line, "\n")];
        }
        return $data;
    }

    /*
     * 高效率计算文件行数
     */

    private function count_line($file) {
        $spl_object = new \SplFileObject($file, 'rb');
        $spl_object->seek(filesize($file));
        $count = $spl_object->key();
        unset($spl_object);
        return $count;
    }

    private function get_mysql_table_fields($tablename) {
        //获取字段列表
        $fields = [];
        $get_field_sql = " select COLUMN_NAME from information_schema.COLUMNS where table_name = '{$tablename}' AND table_schema = '{$this->db_configs['mysql']['name']}'";
        $rs = $this->mysql_conn->query($get_field_sql)->fetchall();
        if ($rs) {
            foreach ($rs as $rs_v) {
                if ($rs_v['COLUMN_NAME'] != 'updated_at') {
                    $fields[] = $rs_v['COLUMN_NAME'];
                }
            }
        } else {
            Log::writelog("$tablename get field failed ......");
        }
        return $fields;
    }

}
