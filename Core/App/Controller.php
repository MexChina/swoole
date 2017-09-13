<?php

namespace Swoole\Core\App;

use Swoole\Core\AppServer;
use \swoole_server;
use Swoole\Core\Log;

/**
 * 控制器父类
 *
 * @author xuelin.zhou
 */
abstract class Controller {

    protected $task_params = [];
    protected $task_back_function = [];
    public $taskers = [];
    public $workers = [];

    public function __construct() {
        $this->taskers = range(AppServer::$instances->swoole->setting['worker_num'], AppServer::$instances->swoole->setting['worker_num'] + AppServer::$instances->swoole->setting['task_worker_num'] - 1);
        $this->taskers = array_diff($this->taskers, array(AppServer::$instances->swoole->worker_id));
        $max_worker_id = defined("MONITOR_WORK") ? (AppServer::$instances->swoole->setting['worker_num'] - 2) : (AppServer::$instances->swoole->setting['worker_num'] - 1);
        if (AppServer::$config->get("server[worker_heart_time]")) {
            $ex_work_id = 2;
        } else {
            $ex_work_id = 1;
        }
        $this->workers = range(0, AppServer::$instances->swoole->setting['worker_num'] - $ex_work_id);
        $this->workers = array_diff($this->workers, array(AppServer::$instances->swoole->worker_id));
    }

    abstract public function init();

    //给客户端返回数据
    public function response($msg, $fd, $errno = 0, $errmsg = "") {
        $reponse['err_no'] = $errno;
        $reponse['err_msg'] = $errmsg;
        $reponse['results'] = $msg;
        return AppServer::$instances->response($reponse, $fd, false);
    }

    /**
     * 通知全部的task进程，主要用来更改task的初始化配置等
     * @param mix $data 要传递给task进程的数据
     * @param function $function 回调函数
     * @param string $aciton task执行的方法名
     */
    function task_all($data, $aciton = null, $function = NULL) {
        $data['action'] = $aciton ? $aciton : "init";
        //要注意这里tasker_id的取值范围，跟sendMessage里面的worker_id不一样
        $taskers = range(0, AppServer::$instances->swoole->setting['task_worker_num'] - 1);
        foreach ($taskers as $tasker_id) {
            $task_id = AppServer::$instances->swoole->task(serialize($data), $tasker_id);
            if (false === $task_id) {
                Log::write_log("send to tasker[$tasker_id] failed ******");
            } else {
                if ($function) {
                    if ($aciton) {
                        unset($data['action']);
                    }
                    $this->task_params["task_" . $task_id] = & $data;
                    $this->task_back_function["task_" . $task_id] = $function;
                }
            }
        }
        return true;
    }

    /**
     * 投递任务到task进程进行异步处理
     * @param mix $data 要传递给task进程的数据
     * @param function $function 回调函数
     * @param string $aciton task执行的方法名
     */
    public function task($data, $function = NULL, $aciton = null) {
        if ($aciton) {
            $data['action'] = $aciton;
        }
        $task_id = AppServer::$instances->swoole->task(serialize($data));
        var_dump($data,$task_id);
        if ($task_id !== false || $task_id != null) {
            if ($function) {
                if ($aciton) {
                    unset($data['action']);
                }
                $this->task_params["task_" . $task_id] = & $data;
                $this->task_back_function["task_" . $task_id] = $function;
            }
            return $task_id;
        } else {
            return false;
        }
    }

    /**
     * task方法发送到其他task进程异步处理后的回调函数
     * @param mix $params 返回的数据
     * @param int $task_id 发送数据时的task_id
     */
    public function task_response($params, $task_id) {
        if ($task_id >= 0 && !empty($this->task_back_function["task_" . $task_id])) {
            $task_back_function = $this->task_back_function["task_" . $task_id];
            $task_params = $this->task_params["task_" . $task_id];
            unset($this->task_back_function["task_" . $task_id]);
            unset($this->task_params["task_" . $task_id]);
            $rs = call_user_func($task_back_function, $params, $task_params, $task_id);
            unset($params);
            unset($task_params);
            unset($task_id);
            unset($task_back_function);
        }
    }

    /**
     * send_to_other_tasker方法发送到其他进程异步处理后的回调函数
     * @param mix $params 返回的数据
     * @param int $task_id 返回数据的进程id
     */
    public function sedmsg_response($params, $work_id) {
        if ($work_id >= 0 && !empty($this->task_back_function["smsg_" . $work_id])) {
            //$params['task_wait_num'] = count($this->task_params) - 1;
            if ($work_id >= AppServer::$instances->swoole->setting['worker_num']) {
                array_push($this->taskers, $work_id);
            } else {
                array_push($this->workers, $work_id);
            }
            $task_back_function = $this->task_back_function["smsg_" . $work_id];
            $task_params = $this->task_params["smsg_" . $work_id];
            unset($this->task_back_function["smsg_" . $work_id]);
            unset($this->task_params["smsg_" . $work_id]);
            call_user_func($task_back_function, $params, $task_params, $work_id);
        }
    }

    /**
     * 发送数据到其他task进程进行异步处理
     * @param mix $data 需要处理的数据
     * @param string $action 处理该数据的方法
     * @param function $back_function 回调函数
     */
    public function send_to_other_tasker($data, $action = "index", $back_function = null) {
        if (!empty($this->taskers)) {
            $send_tasker_id = array_shift($this->taskers); //获取空闲的进程ID
            $is_call_back = empty($back_function) ? FALSE : TRUE; //是否需要回调
            $send_msg_data = array('app' => SWOOLE_APP . 'Task', 'action' => $action, 'params' => $data, 'is_call_back' => $is_call_back);
            $rs = AppServer::$instances->send_to_worker($send_msg_data, $send_tasker_id); //发送数据
            if (!$rs) {
                Log::writelog('send to other tasker failed ......');
                array_push($this->taskers, $send_tasker_id);
                return FALSE;
            }
            if ($is_call_back) {
                //缓存发送数据以便回调
                $this->task_params["smsg_" . $send_tasker_id] = & $data;
                $this->task_back_function["smsg_" . $send_tasker_id] = $back_function;
            } else {
                array_push($this->taskers, $send_tasker_id);
            }
            return $send_tasker_id;
        } else {
            return false;
        }
    }

    /**
     * 发送数据到其他work进程进行异步处理
     * @param mix $data 需要处理的数据
     * @param string $action 处理该数据的方法
     * @param function $back_function 回调函数
     * @param int $worker_id 要发往进程的worker_id
     */
    public function send_to_other_worker($data, $action = "index", $back_function = null, $worker_id = null) {
        if (!empty($this->workers)) {
            if ($worker_id !== null) {
                $send_tasker_id = $worker_id;
                unset($this->workers[array_search($send_tasker_id, $this->workers)]);
            } else {
                $send_tasker_id = array_shift($this->workers); //获取空闲的进程ID
            }
            $is_call_back = empty($back_function) ? FALSE : TRUE; //是否需要回调
            $send_msg_data = array('app' => SWOOLE_APP, 'action' => $action, 'params' => $data, 'is_call_back' => $is_call_back);
            $rs = AppServer::$instances->send_to_worker($send_msg_data, $send_tasker_id); //发送数据
            if (!$rs) {
                Log::writelog('send to other tasker failed ......');
                array_push($this->workers, $send_tasker_id);
                return FALSE;
            }
            if ($is_call_back) {
                //缓存发送数据以便回调
                $this->task_params["smsg_" . $send_tasker_id] = & $data;
                $this->task_back_function["smsg_" . $send_tasker_id] = $back_function;
            } else {
                array_push($this->workers, $send_tasker_id);
            }
            return $send_tasker_id;
        } else {
            return FALSE;
        }
    }

    /**
     * 发送数据到其他所有的work进程进行异步处理
     * @param mix $data 需要处理的数据
     * @param string $action 处理该数据的方法
     * @param function $back_function 回调函数
     * @param int $worker_id 要发往进程的worker_id
     */
    public function send_to_other_worker_all($data, $action = "init", $back_function = null) {
        $workers = range(0, AppServer::$instances->swoole->setting['worker_num'] - 1);
        foreach ($workers as $worker_id) {
            if ($worker_id == $this->swoole->worker_id) {
                call_user_func(array($this, "$action"), $data);
                continue;
            }
            $is_call_back = empty($back_function) ? FALSE : TRUE; //是否需要回调
            $send_msg_data = array('app' => SWOOLE_APP, 'action' => $action, 'params' => $data, 'is_call_back' => $is_call_back);
            $rs = AppServer::$instances->send_to_worker($send_msg_data, $worker_id); //发送数据
            if (!$rs) {
                Log::writelog("send to worker[$worker_id] failed ......");
            }
            if ($is_call_back) {
                //缓存发送数据以便回调
                $this->task_params["smsg_" . $send_tasker_id] = & $data;
                $this->task_back_function["smsg_" . $send_tasker_id] = $back_function;
            }
        }
    }

    function __call($name, $arguments) {
        if (method_exists(swoole_server, $name)) {
            return call_user_func_array(array(AppServer::$instances->swoole, $name), $arguments);
        } elseif (method_exists(AppServer::$instances, $name)) {
            return call_user_func_array(array(AppServer::$instances, $name), $arguments);
        }
    }

    function __get($name) {
        global $_G;
        switch ($name) {
            case "workerparams":
                return $_G;
            case "_G":
                return $_G;
            case "config":
                return AppServer::$config;
            case "idFactory":
                return AppServer::$idFactory;
            case "tables":
                return AppServer::$tables;
            default:
                if (isset(AppServer::$instances->$name)) {
                    return AppServer::$instances->$name;
                } elseif (isset(AppServer::$$name)) {
                    return AppServer::$$name;
                }
        }
    }

}
