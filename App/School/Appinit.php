<?php
/**
 * 刷库模块
 * 1、公司公交信息刷库   traffic
 * 2、 icdc 算法表
 */
namespace Swoole\App\School;
use Swoole\Core\App,
    Swoole\Core\App\AppinitInterface;
use Swoole\Core\AppServer;

class Appinit implements AppinitInterface {


    public function init_cache(){}

    public function worker_init(){
        // 导入学校分数
        $obj=new ImportSchoolScore();
        $obj->index();
    }
    
    public function timer_init(){}
    public function tasker_init(){}
    public function get_worker_number(){}
    public function worker_stop(){}
    public function server_close(){}
}
