<?php

namespace Swoole\App\GearmanRelationsApi;

use Swoole\Core\AppServer;
use Swoole\Core\Log;
use Swoole\Core\Helper\File;
use Swoole\Core\App;
use \swoole_table;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of appinit
 *
 * @author root
 */
class Appinit implements \Swoole\Core\App\AppinitInterface {

    /**
     * 初始化缓存
     *
     * @return viod
     */
    function init_cache() {
        
    }

    /**
     * 获取进程数量, 处理基础数据库时每个库开启一个处理进程
     *
     * @return init
     */
    function get_worker_number() {
        
    }

    /**
     * 进程初始化，进程分发
     *
     * @return viod
     */
    function worker_init() {
        $server = & AppServer::$instances;
        App::$app->main();
    }

    /**
     * 定时任务初始化
     *
     * @return viod
     */
    function timer_init() {
        
    }

    /**
     * 任务进程初始化
     *
     * @return viod
     */
    function tasker_init() {
        
    }

    /**
     * 进程关闭服务时清理工作
     *
     * @return viod
     */
    function worker_stop() {
        if (is_object(App::$app)) {
            App::$app->save_object();
            App::$app = null;
        }
    }

    /**
     * 服务关闭服务时清理工作
     *
     * @return viod
     */
    function server_close() {
        
    }

}
