<?php
namespace Swoole\App\MM;
class MMTask extends \Swoole\Core\App\Controller{
	public function init(){

    }
    public function index($m){

    	$obj = new \GearmanClient();
    	$obj->addServers("192.168.8.38:4730");
    	$param=array(
            'header'=>$this->header(),
            'request'=>array(
                'c' => 'Logic_refresh',
                'm' => 'brushes',
                'p' => array(
                    'resume_id' => $m['ids'],
                    'field' => ['cv_tag']
                )
            )
        );

        $res = $obj->doNormal("icdc_test",json_encode($param));
        error_log(data('Y-m-d H:i:s')."\t".$res."\n",3,'/opt/log/icdc_test');
    }

    private function header(){
        return array(
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
        );
    }
}