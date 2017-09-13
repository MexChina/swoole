<?php
namespace Swoole\App\IcdcQueue;
use Swoole\Core\App;
use Swoole\Core\AppServer;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;

class Appinit implements \Swoole\Core\App\AppinitInterface {

    function init_cache() {}

    function worker_init(){
        App::$app->index();
    }
    function get_worker_number() {}
    function timer_init() {}
    function tasker_init() {}
    function worker_stop() {}
    function server_close() {}
}
