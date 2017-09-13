<?php
/**
 * kafka 数据导入
 */
namespace Swoole\App\Kafka;
use Swoole\Core\Log;
use Swoole\Core\App,Swoole\Core\App\AppinitInterface;
class Appinit implements AppinitInterface {

	//进程只执行一次
    public function init_cache(){
    }
    public function worker_init(){
         App::$app->index();
    }
    public function timer_init(){}
    public function tasker_init(){}
    public function get_worker_number(){}
    public function worker_stop(){}
    public function server_close(){}
}