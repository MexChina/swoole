<?php

namespace Swoole\Core\Lib;

use Swoole\Core\Config;
use Swoole\Core\Log;
use Swoole\Core\Helper\File;
use \GearmanClient;
use Swoole\Core\AppServer;

class MyGearmanClient {

    private $gm;
    private $workName;
    private $conf_file = "/opt/wwwroot/conf/gm.1conf";
    private $header = array(
        'local_ip' => '192.168.8.42',
        'user_ip' => '0',
        'uid' => '0',
        'product_name' => 'bi',
        'session_id' => '0',
        'log_id' => '');
    private $config = array();
    private $log_id = 0;

    function __construct($work_name) {
        $config = Config::instance();
        if (file_exists($this->conf_file)) {
            Log::writelog($this->conf_file." exists ......");
            $this->config = json_decode(File::read_file($this->conf_file), true);
        } else {
            Log::writelog($this->conf_file." is not exists ......");
            $this->config = $config->get("gm[worker_hosts]");
        }
        $this->workName = $work_name;
        $this->gm = new GearmanClient();
        if(empty($this->config[$work_name])){
            Log::writelog("$work_name not exists ......");
            AppServer::$instances->swoole->shutdown();
        }
        $gm_conf = implode(',', $this->config[$work_name]["host"]);
        Log::writelog("connect list $gm_conf......");
        $r = $this->gm->addServers($gm_conf);
    }

    function get($param) {
        $this->log_id++;
        $this->header['log_id'] = "192168842" . SWOOLE_WORKER_ID . "$this->log_id";
        $params = array(
            'request' => $param,
            'header' => $this->header
        );
        Log::write_notice(var_export($params, true));
        $result = $this->gm->doNormal($this->workName, msgpack_pack($params));

        switch ($this->gm->returnCode()) {
            case '47':
                Log::writelog("work over time, errorCode:" . $this->gm->returnCode() . " ......");
                break;
            case GEARMAN_WORK_FAIL:
                Log::writelog("work return faild, errorCode:" . $this->gm->returnCode() . ",error(" . $this->gm->getErrno() . "):" . $this->gm->error() . " ......");
                break;
            case GEARMAN_SUCCESS:
                $res = msgpack_unpack($result);
                $response = $res['response'];
                if ($res['err_no'] == 0) {
                    if (empty($response['results'])) {
                        $response['results'] = array("totalcount" => 0);
                    }
                    return $response['results'];
                } else {
                    Log::writelog('work failed, res:' . json_encode($res, JSON_UNESCAPED_UNICODE));
                    return false;
                }
                break;
            default:
                Log::writelog("work unknow error, errorCode:" . $this->gm->returnCode() . ",error(" . $this->gm->getErrno() . "):" . $this->gm->error() . " ......");
                return FALSE;
                break;
        }
        return false;
    }

}
