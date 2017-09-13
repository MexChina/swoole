<?php

namespace Swoole\Core\App;

use Swoole\Core\AppServer;
use \swoole_server;
use Swoole\Core\App;

/**
 * 控制器父类
 *
 * @author xuelin.zhou
 */
abstract class Controller {

    protected $task_params = [];
    protected $task_back_function = [];

    public function __construct() {
        
    }

    abstract public function init();

    //给客户端返回数据
    protected function response($data, $fd, $recordlog = true) {
        return AppServer::$instances->response($data, $fd, $recordlog);
    }

    //任务投递 
    protected function task($data, $function = NULL) {
        $task_id = AppServer::$instances->swoole->task(serialize($data));
        if ($function) {
            $this->task_params[$task_id] = & $data;
            $this->task_back_function[$task_id] = $function;
        }
        return $task_id;
    }

    public function task_response($params, $task_id) {
        if ($task_id >= 0 && !empty($this->task_back_function[$task_id])) {
            $params['task_wait_num'] = count($this->task_params) - 1;
            call_user_func($this->task_back_function[$task_id], $params, $this->task_params[$task_id]);
            unset($this->task_back_function[$task_id]);
            unset($this->task_params[$task_id]);
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
