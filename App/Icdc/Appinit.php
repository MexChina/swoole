<?php

namespace Swoole\App\ICDC;
use Swoole\Core\App;
use Swoole\Core\App\AppinitInterface;
class Appinit implements AppinitInterface {
    public function init_cache(){}
    public function get_worker_number(){}
    public function worker_init(){
        App::$app->index();
    }
    public function tasker_init(){}
    public function timer_init(){}
    public function worker_stop(){}
    public function server_close(){}
}
