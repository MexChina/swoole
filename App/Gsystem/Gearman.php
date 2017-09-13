<?php
/**
 * Created by PhpStorm.
 * User: qing
 * Date: 16-12-15
 * Time: 下午7:36
 */
namespace Swoole\App\Gsystem;

use Swoole\Core\Lib\Worker;
class Gearman{

    protected $gsystem_db;

    public function init(){
        $this->gsystem_db = $this->db('gsystem');
    }

    public function gsystem_basic(){
        

        $worker = new Worker(__FUNCTION__);
        $worker->worker(function($request){

            try{
                if(!is_array($request) || empty($request['c']) || empty($request['m']) || empty($request['p'])){
                    throw new \Exception("Parameter error, not in accordance with the interface format specification calls!",10001);
                }
                $file_name = ucfirst($request['c']);
                if(!file_exists(SWOOLE_ROOT_DIR."Gsystem/Logic/".$file_name.'.php')){
                    throw new \Exception("[c] ".$request['c']." not exists!",10002);
                }

                $c = "Swoole\\Gsystem\\Logic\\".$file_name;
                $controller = new $c();
                $method = strtolower($request['m']);

                if(!method_exists($controller,$method)){
                    throw new \Exception("[m] ".$request['m']." not exists!",10003);
                }

                $result = $controller->$method($request['p']);
            }catch (\Exception $e){
                return array('err_no'=>$e->getCode(),'err_msg'=>$e->getMessage(),'results'=>array());
            }


            return array('err_no'=>0,'err_msg'=>'','results'=>$result);
        });
    }

}
