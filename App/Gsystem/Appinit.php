<?php

namespace Swoole\App\Gsystem;
use Swoole\Core\App;
use Swoole\Core\AppServer;
use Swoole\Core\App\AppinitInterface;
class Appinit implements AppinitInterface {
    public function init_cache(){}
    public function get_worker_number(){}
    public function worker_init(){
        $work_id = AppServer::instance()->workerid;

        if($work_id < 8) {
            //获取公司corporation列表信息
            App::$app->corporation();
        }elseif(in_array($work_id, [9,10])){
            // 根据地址获取交通信息
            App::$app->traffic_info();
        }elseif(in_array($work_id, [11])){
            // gsystem_traffic刷库
            $traffic = new RefreshTraffic();
            $traffic->refresh_gsystem_corporation_traffic();
        }elseif(in_array($work_id, [12])){
            // tobusiness_traffic刷库
            $traffic = new RefreshTraffic();
            $traffic->refresh_tobusiness_corporation_traffic();
        }else{
            //对外接口
            App::$app->index();
        }

    }
    public function tasker_init(){}
    public function timer_init(){}
    public function worker_stop(){}
    public function server_close(){}
}
