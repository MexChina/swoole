<?php

namespace Swoole\App\GearmanRelationsApi;

use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Swoolclient;
use Swoole\Core\Lib\Gearman;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DataEtl
 *
 * @author root
 */
class GearmanRelationsApi extends \Swoole\Core\App\Controller {

    private $gm_config;

    public function init() {
        $this->gm_config = $this->config->get("gm");
    }

    function main() {
        //$atomic_count->add(1);
        ///swoole_set_process_name("swooleGearmanGetRelationsWorker{$worker_id}");

        $gearman = new Gearman($this->gm_config["gm_host"], $this->gm_config["gm_port"], $this->gm_config["worker_name"]);
        $gearman->worker(function($request) {
            if (empty($request['header']['local_ip']) || empty($request['header']['product_name']) || empty($request['header']['log_id'])) {
                Log::write_log("header is empty " . var_export($request, TRUE) . "******");
                $response['err_no'] = 10001;
                $response['err_msg'] = 'header is empty, please set `local_ip`,`product_name`,`log_id` ......';
                return $response;
            }
            $client = new Swoolclient($this->gm_config["server_host"], $this->gm_config["server_port"], -1, 1);
            if (!$client->connect()) {
                Log::write_log("connect {$this->gm_config["server_host"]}:{$this->gm_config["server_port"]} failed ******");
            }
//            $start_time = microtime(true);
            //type=App&appname=Count&action=index&params=[]
            $params["type"] = "App";
            $params["app"] = $request['m'] ?? $this->gm_config["server_app"];
            //为了兼容老接口
            $params["app"] = $params["app"] == "GPDtMysql" ? "GetRelations" : $params["app"];
            $params["action"] = $request['c'] ?? $this->gm_config["server_action"];
            $params["params"]['request'] = $request['request']['p'];
            $params["params"]['header'] = $request['header'];
            $client->send($params);
            while (true) {
                $rs = $client->receive();
                //Log::write_log("receive data is " . var_export($rs, TRUE));
                if (isset($rs['err_no'])) {
                    break;
                }
            }
            $client->close();
//            $use_time = intval((microtime(true) - $start_time) * 1000);
            //$atomic_time->add($use_time);
//            Log::write_info("use $use_time ms ......");
            return $rs;
        });
    }

    public function client_close($data) {
        $fd = $data["fd"];
        Log::write_log("client[{$fd}] closed ......");
    }

    public function __destruct() {
        $this->mysql_conn->close();
        pg_close($this->pg_conn);
    }

    public function __wakeup() {
        $this->init();
    }

}
