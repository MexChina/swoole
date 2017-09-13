<?php
/**
 * 1、传输格式为json
 * 2、采用mcp模式
 */

namespace Swoole\Core\Lib;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
class Gearman2{

    private $header = array();  //接口请求头部信息
    private $host;              //接口主机地址
    private $port;              //接口主机端口
    private $api;               //接口标识符
    private $empty_respon;      //接口请求为空默认返回的数据
    private $log_path;

    public function __construct($host,$port,$api){
        $this->host = $host;
        $this->port = $port;
        $this->api = $api;
        $this->set_empty_respone();
        $this->set_header();
        $this->log_path = "/gearman";
    }

    /**
     * 设置接口头部信息
     * @param array $arr
     */
    public function set_header($arr=array()){
        if(empty($arr) && empty($this->header)){
            $this->header = array(
                'product_name'=>'',
                'uid'=>'',
                'uname'=>'dongqing.shi',
                'session_id'=>'',
                'user_ip'=>'',
                'local_ip'=>'127.0.0.1',
                'log_id'=>date("YmdHis").rand(10,99)
            );
        }elseif(!empty($arr)){
            $this->header = $arr;
        }
    }

    /**
     * 设置接口请求参数为空的返回值
     * @param array $arr
     */
    public function set_empty_respone($arr=array()){
        $this->empty_respon = $arr;
    }

    /**
     * client 调用
     * @param $data array  要发送的数据
     * @return mixed
     */
    public function client($request,$is_json=false){
        $client = new \GearmanClient();
        $client->addServer($this->host,$this->port);
        $param['header'] = $this->header;
        $param['request']=$request;
        $send_data = $is_json ? json_encode($param) : msgpack_pack($param);
        Log::write_info("$this->api 请求: ".$send_data."\n","/gearman");
        $return = $client->doNormal($this->api, $send_data);
        $ret = msgpack_unpack($return);
        Log::write_info("$this->api 返回: ".json_encode($ret)."\n","/gearman");
        return $ret['response'];
    }

    /**
     * worker 调用
     * @param \Closure $callback 对象
     */
    public function worker(\Closure $callback){
        $gmworker = new \GearmanWorker();
        try {
            $gmworker->addServer($this->host,$this->port);
        } catch (\Exception $e) {
            Log::write_log('连接Gearman服务器出错:' . $e->getTraceAsString());
        }

        $gmworker->addFunction($this->api,function($job)use($callback){
            $request = $job->workload();
            $result = json_decode($request,true);
            if(isset($result['header'])) $this->set_header($result['header']);

            Log::write_log("{$result['header']['uname']} {$result['header']['local_ip']} 数据处理中...");
            Log::write_info("\n请求:".json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),"/gearman");
            System::exec_time();

            if($result['request']){
                $response = call_user_func_array($callback,array($result['request']));
            }
            if(empty($this->header)) $this->set_header();
            $response_data['header'] = $this->header;
            $response_data['response'] = array(
                'err_no'        =>  isset($response['err_no']) ? $response['err_no'] : 0,
                'err_msg'       =>  isset($response['err_msg']) ? $response['err_msg'] : '',
                'results'       =>  isset($response['results']) ? $response['results'] : $this->empty_respon
            );
            Log::write_log("请求处理成功，用时： " . System::exec_time() . " ms, 内存使用： " . System::get_used_memory());
            Log::write_info("\n返回:".json_encode($response_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),"/gearman");
            return json_encode($response_data);
        });
        Log::write_log("接口服务器启动成功，等待连接...");
        while($gmworker->work()){
            if ($gmworker->returnCode() != GEARMAN_SUCCESS) {
                Log::write_info("接口服务器返回失败".$gmworker->returnCode());
                break;
            }
        }
    }
}