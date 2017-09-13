<?php
/**
 * c端简历  和  b端简历的映射关系
 * 员工保留 和  b端简历的映射关系
 * 猎头招聘 和  b端简历的映射关系
 */
namespace Swoole\App\MM;
use Swoole\Core\App,Swoole\Core\App\AppinitInterface;
class Appinit implements AppinitInterface {
    public function init_cache(){}
    public function worker_init(){
         App::$app->index();
    }
    public function timer_init(){}
    public function tasker_init(){}
    public function get_worker_number(){}
    public function worker_stop(){}
    public function server_close(){}
}