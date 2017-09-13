<?php

namespace Swoole\App\Gsystem;
use Swoole\Core\Log;
use Swoole\Core\AppServer;
use Swoole\Core\Lib\Cache\Redis;
use Swoole\Core\Lib\Worker;
use Swoole\App\Gsystem\Controller;
class Gsystem extends \Swoole\Core\App\Controller{

    private $work_id;
    public function init(){
        $this->work_id = AppServer::instance()->workerid;
    }

    /** 对外接口
     * 根据work 设置进程 读写
     *
     * c=>controller/文件名     首字母大写
     * m=>文件中的方法名              小写
     * p=>方法名中的参数
     */
    public function index(){
        $worker = new Worker("api_gsystem");
        $obj['crop_tag'] = new Worker("corp_tag");
        $config = AppServer::$config->get('db[redis]');
        $obj['redis'] = new Redis($config);
        $obj['mysql'] = AppServer::db('gsystem');

        $worker->worker(function($request)use($obj){
            if(!is_array($request) || empty($request['c']) || empty($request['m']) || empty($request['p'])){
                Log::write_log("参数错误,没有按照接口格式规范调用...");
                return array('err_no'=>1,'err_msg'=>'参数错误,没有按照接口格式规范调用...','results'=>array());
            }
            $controller = new Controller\Logo($obj);
            $method = strtolower($request['m']);
            return $controller->$method($request['p']);
        });
    }

    /** select
     * 获取公司列表
     * p=>
     *      type    > 1 || 0
     *      field   > "id,name,...."
     *      where   > ""
     *      order   > "id desc"
     *      limit   > 10
     *      page
     *      page_size
     *
     */
    public function corporation(){
        $worker = new Worker("api_gsystem_corporation");
        $config = AppServer::$config->get('db[redis]');
        $config['prefix']='corporation_';
        $obj['redis'] = new Redis($config);
        $worker->worker(function($request)use($obj){
            if(!is_array($request) || empty($request['p']) || empty($request['m'])){
                Log::write_log("参数错误,没有按照接口格式规范调用...");
                return array('err_no'=>1,'err_msg'=>'参数错误,没有按照接口格式规范调用...','results'=>array());
            }
            $controller = new Controller\Corporation($obj);
            $method = strtolower($request['m']);
            if(!method_exists($controller,$method)){
                Log::write_log("$method functions not exists...");
                return array('err_no'=>1,'err_msg'=>"请求的 m参数：$method 不存在，请检查接口参数是否正确...",'results'=>array());
            }
            return $controller->$method($request['p']);
        });
    }

    /**
     * 交通信息
     *
     */
    public function traffic_info(){
        $worker = new Worker("api_gsystem_traffic_info");
        $config = AppServer::$config->get('db[redis]');
        $config['prefix']='corporation_';
        $obj['redis'] = new Redis($config);
        $worker->worker(function($request)use($obj){
            if(!is_array($request) || empty($request['p']) || empty($request['m'])){
                Log::write_log("参数错误,没有按照接口格式规范调用...");
                return array('err_no'=>1,'err_msg'=>'参数错误,没有按照接口格式规范调用...','results'=>array());
            }
            $controller = new Controller\Corporation($obj);
            $method = strtolower($request['m']);
            if(!method_exists($controller,$method)){
                Log::write_log("$method functions not exists...");
                return array('err_no'=>1,'err_msg'=>"请求的 m参数：$method 不存在，请检查接口参数是否正确...",'results'=>array());
            }
            return $controller->$method($request['p']);
        });
    }


}