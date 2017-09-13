<?php
/**
 * 1、传输格式为json
 * 2、采用mcp模式
 */
namespace Swoole\Core\Lib;
use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use \Exception;
class Worker{

    private $header = array();  //接口请求头部信息
    private $api;               //接口标识符
    private $empty_respon;      //接口请求为空默认返回的数据
    private $host;
    private $time_out = 5000000;

    /**
     * $host, $port, $api, $is_client = false
     * @param [type] $api [description]
     */
    public function __construct(string $api,string $gm_config="/opt/wwwroot/conf/gm.conf")
    {
        if(!file_exists($gm_config)) throw new Exception($gm_config." not found!",1);
        $servers = [];
        $config_text = json_decode(file_get_contents($gm_config),true);
        if (isset($config_text[$api]) && $config_text[$api]['host']){
            foreach ($config_text[$api]['host'] as $host){
                array_push($servers, $host);
            }
        }else{
            throw new Exception("$api register host not exists.",1);
        }
        $this->host = array_unique($servers);
        $this->api = $api;
        $this->set_empty_respone();
        $this->set_header();
    }


    /**
     * 设置接口头部信息
     * @param array $param [description]
     */
    public function set_header(array $param=[])
    {
        $this->header = [
            'product_name'  =>  $param['product_name'] ?? 'BIService',
            'uid'           =>  $param['uid'] ?? '9',
            'session_id'    =>  $param['session_id'] ?? '0',
            'uname'         =>  $param['uname'] ?? 'BIServer',
            'version'       =>  $param['version'] ?? '0.1',
            'signid'        =>  $param['signid'] ?? 0,
            'provider'      =>  $param['provider'] ?? 'icdc',
            'ip'            =>  $param['ip'] ?? '0.0.0.0',
            'user_ip'       =>  $param['user_ip'] ?? '0.0.0.0',
            'local_ip'      =>  $param['local_ip'] ?? '0.0.0.0',
            'log_id'        =>  $param['log_id'] ?? uniqid('bi_'),
            'appid'         =>  $param['appid'] ?? 999,
        ];
    }

    /**
     * 设置接口请求参数为空的返回值
     * @param array $arr
     */
    public function set_empty_respone(array $arr=[])
    {
        $this->empty_respon = $arr;
    }


    /**
     * 客户端调用
     * @param  array        $request       请求参数
     * @param  bool|boolean $request_json  是否采用json打包
     * @param  bool|boolean $response_json 是否采用json解包
     * @param  bool|boolean $asyn          是否采用异步
     * @param  int|integer  $j             如果是否重试次数
     * @return [type]                      [description]
     */
    public function client(array $request,bool $request_json=false,bool $response_json=false,bool $asyn=false,int $j=3)
    {
        $client = new \GearmanClient();
        foreach($this->host as $host){
            $client->addServers($host);
        }
        $client->setTimeout(5000);
        $param['header'] = $this->header;
        $param['request']=$request;
        $this->access($param,'REQ:');
        $send_data = $request_json ? json_encode($param) : msgpack_pack($param);
        if($asyn){
            $client->doBackground($this->api,$send_data,$this->header['log_id']);
        }else{
            $i=0;
            do{
                $start_time = microtime(true);
                $return = $client->doNormal($this->api, $send_data,$this->header['log_id']);
                switch($client->returnCode()){
                    case GEARMAN_WORK_FAIL:
                        error_log(sprintf(date('Y-m-d H:i:s')."\t{$this->api}\t<$j> param:%s", json_encode($param,JSON_UNESCAPED_UNICODE))."\n",3,"/opt/log/{$this->api}_client_fail.".date('Ym'));
                        $i++;
                        break;
                    case GEARMAN_SUCCESS:
                        $ret = $response_json ? json_decode($return,true) : msgpack_unpack($return);
                        $this->access($ret,'RSP:');
                        $time = (microtime(TRUE)-$start_time)*1000;
                        error_log(date('Y-m-d H:i:s')."\t".$this->api."\tsuccess\t$time\n",3,"/opt/log/gearman_client_access.".date("Y-m-d"));
                        return $ret['response'] ?? array();
                    case GEARMAN_WORK_DATA:
                    case GEARMAN_WORK_STATUS:
                        break; 
                    default:
                        error_log(sprintf(date('Y-m-d H:i:s')."\t{$this->header['log_id']}\t{$this->api}\t<$j> Failed RET: %s %s", $client->returnCode(),json_encode($param,JSON_UNESCAPED_UNICODE))."\n",3,"/opt/log/{$this->api}_client_fail.".date('Ym'));
                        $time = (microtime(TRUE)-$start_time)*1000;
                        error_log(date('Y-m-d H:i:s')."\t".$this->api."\tfailed\t$time\n",3,"/opt/log/gearman_client_access.".date("Y-m-d"));
                        $i++;
                        break;
                }
            }while($client->returnCode() != GEARMAN_SUCCESS && $i<$j);
            return false;
        }
    }

    /**
     * worker 调用
     * @param \Closure $callback 对象
     */
    public function worker(\Closure $callback)
    {
        $gmworker = new \GearmanWorker();
        try {
            foreach($this->host as $host){
                $gmworker->addServers($host);
                Log::writelog($this->api."\t".$host." success...");
            }
        } catch (\Exception $e) {
            Log::writelog('Gearman server connection faild:' . $e->getTraceAsString());
            throw new Exception('Gearman server connection faild:' . $e->getTraceAsString(), 1);
        }

        $gmworker->addFunction($this->api,function($job)use($callback){
            System::exec_time();
            $request = $job->workload();
            $handle = $job->handle();
            $unique = $job->unique();
            $msgpack = false;
            $result = json_decode($request,true);
            if(!is_array($result)){
                $msgpack = true;
                $result = msgpack_unpack($request);
            }
            
            $this->access($result,'REQ:');
            if(isset($result['header'])) $this->set_header($result['header']);
            if(empty($this->header)) $this->set_header();
            
            if(empty($result['request']) || empty($result['header'])){
                return $this->response('',1,'request or header not empty!',$msgpack);
            }
       
            if(isset($result['header'])) $this->set_header($result['header']);
            $this->access($result,'REQ:');
            Log::writelog("{$handle}\t{$unique}\t{$result['header']['uname']}\t{$result['header']['local_ip']}");
            try{
                $response = call_user_func_array($callback,array($result['request']));
                if(empty($this->header)) $this->set_header();
                $response_data['header'] = $this->header;
                $response_data['response'] = array(
                    'err_no'        =>  isset($response['err_no']) ? $response['err_no'] : 0,
                    'err_msg'       =>  isset($response['err_msg']) ? $response['err_msg'] : '',
                    'results'       =>  isset($response['results']) ? $response['results'] : $this->empty_respon
                );
            }catch(\Exception $e){
                if(empty($this->header)) $this->set_header();
                $response_data['header'] = $this->header;
                $response_data['response'] = array(
                    'err_no'        =>  $e->getCode(),
                    'err_msg'       =>  $e->getMessage(),
                    'results'       =>  ''
                );
            }
            Log::writelog("{$handle}\t{$unique}\t[{$result['header']['local_ip']}:{$result['header']['product_name']}:{$result['header']['log_id']}] reponse [" . count($response['results']) . "] datas,use time:" . System::exec_time() . "ms, memory used:" . System::get_used_memory());
            $this->access($response_data,'RSP:');
            return $msgpack ? msgpack_pack($response_data) : json_encode($response_data,JSON_UNESCAPED_UNICODE);
        });
        Log::writelog("start {$this->api} success...");
        while($gmworker->work()){
            if ($gmworker->returnCode() != GEARMAN_SUCCESS) {
                Log::writelog("Gearman faild code:".$gmworker->returnCode());
                break;
            }
        }
    }

    /**
     * 打包返回
     * @param  [type]  $ret  [description]
     * @param  integer $err  [description]
     * @param  string  $msg  [description]
     * @param  boolean $type [description]
     * @return [type]        [description]
     */
    private function response(array $ret,int $err=0,string $msg='',bool $type=false):string
    {
        $data = array(
            'header'=>$this->header,
            'response'=>array(
                        'err_no'  =>  $err,
                        'err_msg' =>  $msg,
                        'results' =>  $ret
                    )
        );
        $this->access($data,'RSP:');
        return $type ? msgpack_pack($data) : json_encode($data,JSON_UNESCAPED_UNICODE);
    }

    /**
     * 访问请求日志
     * @param  [type] $msg [description]
     * @return [type]      [description]
     */
    private function access($msg,string $type="RSP:")
    {
        error_log(date('Y-m-d H:i:s')."\t{$this->header['log_id']}\t$type\t".json_encode($msg,JSON_UNESCAPED_UNICODE)."\n",3,"/opt/log/".$this->api."_access.".date('Y-m-d'));
    }
}