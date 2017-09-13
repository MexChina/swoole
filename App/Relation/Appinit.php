<?php
namespace Swoole\App\Relation;
use Swoole\Core\App;
use Swoole\Core\AppServer;
use Swoole\Core\App\AppinitInterface;
class Appinit implements AppinitInterface {
    public function init_cache(){}
    public function get_worker_number(){}
    public function worker_init(){
        $work_id = AppServer::instance()->workerid;

        if($work_id == 0){
            App::$app->company();
        }
        elseif($work_id == 1){
            App::$app->update();
        }elseif($work_id < 3){
            App::$app->search();
        }else{
            App::$app->resume();
        }

    }
    public function tasker_init(){}
    public function timer_init(){}
    public function worker_stop(){}
    public function server_close(){}
}
