<?php

namespace Swoole\App\Refresh;

use Swoole\Core\Log;
use Swoole\Core\Lib\AsyncMysql;
use Swoole\Core\Helper\System;

class Refresh extends \Swoole\Core\App\Controller {

    private $task_number = 0;
    private $count_num = 0;
    private $page_size = 500;
    private $task_pool = [];
    private $cache_task_data = [];
    public $start_id = 1;

    public function init() {
        
    }

    // public function index(){
    //     $this->task_pool = range(($this->swoole->setting['task_worker_num'] / $this->swoole->setting['worker_num']) * ($this->swoole->worker_id), ($this->swoole->setting['task_worker_num'] / $this->swoole->setting['worker_num']) * ($this->swoole->worker_id + 1) - 1);
    //     $read_db_config = $this->config->get("db[slave_icdc_{$this->swoole->worker_id}]");
    //     Log::write_log(var_export($read_db_config,true));
    // }

    public function index() {

        $this->task_pool = range(($this->swoole->setting['task_worker_num'] / $this->swoole->setting['worker_num']) * ($this->swoole->worker_id), ($this->swoole->setting['task_worker_num'] / $this->swoole->setting['worker_num']) * ($this->swoole->worker_id + 1) - 1);
        $read_db_config = $this->config->get("db[slave_icdc_{$this->swoole->worker_id}]");
        //Log::write_log(var_export($this->task_pool, true));
        if (is_array($read_db_config)) {
            // $this->read_db = new AsyncMysql($read_db_config, 3, 300);
            // $this->read_db = $this->db("slave_icdc_".$this->swoole->worker_id);
            $this->read_db = new \mysqli($read_db_config['host'], $read_db_config['user'], $read_db_config['passwd'], $read_db_config['name'],$read_db_config['port']);
            Log::write_log("icdc_{$this->swoole->worker_id} read db connect success ...");
        } else {
            Log::write_log("icdc_{$this->swoole->worker_id} read db connect failed ******");
            $this->swoole->shutdown();
        }

        // Log::write_log("ddddddddddddddddddddddd");

        $sql = "SELECT re.id,re.compress FROM `resumes` ra left join `resumes_extras` as re on ra.id=re.id WHERE " . ($this->start_id ? "ra.id >{$this->start_id} and" : "") . " ra.is_deleted='N' ORDER BY ra.id asc LIMIT $this->page_size";
        Log::write_log("read sql start: $sql");
        $start_time = microtime(true);
        
        // $this->read_db->query($sql, function($result) use ($start_time, $sql) {
        //     if (empty($result)) {
        //         Log::write_log("icdc_{$this->swoole->worker_id} comlated ...");
        //         return;
        //     } else {
        //         Log::write_log("【icdc_{$this->swoole->worker_id}】read sql sucess,use " . (microtime(true) - $start_time) . "s: $sql");
        //     }
        //     $datas['db_id'] = $this->swoole->worker_id;
        //     $datas['start_id'] = $this->start_id;
        //     $datas['start_time'] = microtime(true);
        //     while ($row = $result->fetch_assoc()) {
        //         $this->start_id = intval($row['id']);
        //         if (!empty($row['compress'])) {
        //             $datas['data'][] = $row;
        //         }
        //     }
        //     if (!empty($this->task_pool)) {
        //         $this->index();
        //     }
        //     $this->task_send($datas);
        //     unset($start_time, $sql, $datas);
        // });
    }

    function task_send($datas) {
        if (empty($datas)) {
            return;
        }
        //Log::write_log(count($datas['data'])." datas send to task...");
        if (empty($this->task_pool)) {
            //Log::write_log("task_number:$this->task_number, task is busy, cache datas ...");
            array_push($this->cache_task_data, $datas);
            return;
        }
        while (TRUE) {
            $task_id = array_pop($this->task_pool);
            $datas['task_id'] = $task_id;
            $send_task_id = $this->task($datas, array($this, "task_back"), 'algorithm',$task_id);
            if ($send_task_id !== false) {
                $this->task_number ++;
                Log::write_log("icdc_{$this->swoole->worker_id}, start_id:{$this->start_id},task_worker_id:【{$task_id}】 send task success ......");
                usleep(2000);
                break;
            } else {
                array_push($this->task_pool, $task_id);
                sleep(1);
            }
        }
    }

    function task_back($data, $request, $task_id) {
        $this->task_number--;
        array_push($this->task_pool, $request['task_id']);
        if (!empty($this->cache_task_data)) {
            $this->task_send(array_pop($this->cache_task_data));
            if (empty($this->cache_task_data)) {
                $this->index();
            }
        }
        if ($data) {
            $this->count_num += $this->page_size;
            Log::write_log("task_number:$this->task_number,icdc_{$this->swoole->worker_id}:$this->count_num, use 【" . (microtime(true) - $request['start_time']) . "】s, memory use " . System::get_used_memory() . " ...");
        }
    }

    public function __destruct() {
        $this->read_db->close();
    }

    public function save_object() {
        $object_cache_name = "{$this->swoole->worker_id}_" . SWOOLE_APP . "_Object.data";
        Log::write_log("save current process data to file $object_cache_name ......");
        File::write_file(SWOOLE_APP_DIR . "cache/" . SWOOLE_ENVIRONMENT . "/{$object_cache_name}", bin2hex(gzcompress(serialize($this))));
    }

    public function __sleep() {
        unset($this->resume);
        $this->read_db->close();
        return array('start_id');
    }

    public function __wakeup() {
        //$this->init();
    }

}
