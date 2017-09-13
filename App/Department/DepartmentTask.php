<?php
namespace Swoole\App\Department;

use Swoole\Core\App\Controller;
use Swoole\Core\AppServer;
use Swoole\Core\Log;

class DepartmentTask extends Controller{
    
    private $mysqli;
    public function init(){
        $this->mysqli = AppServer::db("api");
    }

    /**
     * @param $params  array('id','company_id','department','from_worker_id')
     */
    public function index($params){
        unset($params['from_worker_id']);
        $values = '';
        foreach($params as $p){
            $values .= "('".$p['id']."',";
            $values .= "'".$p['name']."',";
            $values .= "'".date('Y-m-d H:i:s')."'),";
        }
        $values = rtrim($values,',');
        $sql = "replace into `department_architecture` (`id`,`name`,`create_time`) values $values";
        $res = $this->mysqli->query($sql);
        $status = $res->result ? "success" : "fail";
        Log::write_log("write department_architecture $status...");
    }

    public function __destruct(){
        $this->mysqli->close();
    }
}