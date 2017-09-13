<?php
/**
 * 刷库模块
 * 1、公司公交信息刷库   traffic
 * 2、 icdc 算法表
 */
namespace Swoole\App\Algorithms;
use Swoole\Core\App,
    Swoole\Core\App\AppinitInterface;
class Appinit implements AppinitInterface {


    public function init_cache(){}

    public function worker_init(){

        /**
         * 刷公司公交信息
         */
//        $traffic = new Traffic();
//        $traffic->start();

        /**
         * 刷icdc算法
         */
        App::$app->start();
    }
    
    public function timer_init(){}
    public function tasker_init(){}
    public function get_worker_number(){}
    public function worker_stop(){}
    public function server_close(){}
}
