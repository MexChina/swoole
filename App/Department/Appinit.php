<?php
namespace Swoole\App\Department;

use Swoole\Core\App;
use Swoole\Core\App\AppinitInterface;
use Swoole\Core\AppServer;
use Swoole\Core\Log;
use \swoole_table;
class Appinit implements AppinitInterface {

    public function init_cache(){
        
        //创建内存表
        $department_table = new swoole_table(4000000);                      //1千万  82789162
        $department_table->column('id', swoole_table::TYPE_INT, 4);
        $department_table->create();
        AppServer::$tables['department'] = $department_table;

        global $ATOMIC;
        $mysqli = AppServer::db("api");
        $result = $mysqli->query("select `id`,`name` from `department_architecture` order by id desc");
        $row = $result->fetchall();
        $start_id = empty($row) ? 1 : $row[0]['id'];
        $ATOMIC = new \swoole_atomic($start_id);    //取最大一个当下次累增
        foreach($row as $r){
            AppServer::$tables['department']->set($r['name'],array('id'=>$r['id']));
        }
    }
    
    public function get_worker_number(){}
    
    public function worker_init(){
        App::$app->index();   //接口服务端
    }
    
    public function tasker_init(){}
    
    public function timer_init(){}
    
    public function worker_stop(){}
    
    public function server_close(){}
}
