<?php

namespace Swoole\Core;

use Swoole\Core\Log;
use Swoole\Core\AppServer;
use \swoole_server;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of App
 *
 * @author xuelin.zhou
 */
class App {

    private static $appinit;
    public static $apps;
    public static $app;
    private static $fd_apps = [];

    //应用初始化
    public static function init($action) {
        $action = strtolower($action);
        //Log::writelog("run action {$action}......");
        $app_path = SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . "/";
        if (($action == "tasker_init" || $action == "worker_init")) {
            if ($action == "tasker_init") {
                $appname = SWOOLE_APP . "Task";
            } elseif ($action == "worker_init") {
                $appname = SWOOLE_APP;
            }
            if (!self::$apps[$appname]) {
                if (file_exists($app_path . "{$appname}.php")) {
                    $full_appname = "Swoole\\App\\" . SWOOLE_APP . "\\{$appname}";
                    self::$apps[$appname] = new $full_appname;
                    self::$app = & self::$apps[$appname];
                    self::$apps[$appname]->init();
                }
            }
        }
        if (self::$appinit) {
            $return = self::$appinit->$action();
        } else {
            $appinit_name = "Swoole\\App\\" . SWOOLE_APP . "\\Appinit";
            self::$appinit = new $appinit_name;
            if (method_exists(self::$appinit, $action)) {
                $return = self::$appinit->$action();
            } else {
                Log::writelog("Appinit action:{$action} is not exist ......");
                die;
            }
        }
        return $return;
    }

    //执行接受到消息以后的控制器调用
    public static function doaction($data) {
        $fd = empty($data['params']['fd']) ? 0 : $data['params']['fd'];
        $data['app'] = !empty($data['app']) ? ucfirst($data['app']) : (($fd && !empty(self::$fd_apps[$fd])) ? self::$fd_apps[$fd] : SWOOLE_APP);
        if ($fd && empty(self::$fd_apps[$fd]) && $data['type'] != "close") {
            self::$fd_apps[$fd] = $data['app'];
        } elseif ($fd && $data['type'] == "close" && !empty(self::$fd_apps[$fd])) {
            unset(self::$fd_apps[$fd]);
        }
        $data['type'] = !empty($data['type']) ? $data['type'] : "app"; //脚本类型，app:worker进程运行的主脚本，task:tasker进程运行的任务脚本
        $appaction = !empty($data['action']) ? $data['action'] : "index";
        $params = !empty($data['params']) ? $data['params'] : array();
        $return = null;
        $appname = $data['type'] == "task" ? ($data['app'] . "Task" ) : $data['app'];
        if ($data['app'] != SWOOLE_APP) {
            if (empty(self::$apps[$appname])) {
                $full_appname = "Swoole\\App\\" . SWOOLE_APP . "\\$appname";
                self::$apps[$appname] = new $full_appname;
                self::$apps[$appname]->init();
            }
        }
        //Log::writelog("run app:{$appname} action:{$appaction}......");
        self::$app = & self::$apps[$appname];
        if ($data['worker_type'] == 'task' && isset($data['task_id'])) {
            $return = call_user_func_array(array(self::$apps[$appname], "task_response"), array("params" => $params, "task_id" => $data['task_id']));
        } elseif (method_exists(self::$app, $appaction)) {
            $return = call_user_func_array(array(self::$apps[$appname], $appaction), array("params" => $params));
        } else {
            Log::writelog("app:{$appname} is not exist action:{$appaction}......");
        }
        return $return;
    }

}