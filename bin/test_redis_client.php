<?php

use Swoole\Core\Lib\Swoolclient;
use Swoole\Core\Helper\System;
use \swoole_server;
use \swoole_atomic;

$serv = new swoole_server("0.0.0.0", 19501);
$atomic_count = new swoole_atomic();
$atomic_time = new swoole_atomic();
$per_reponse_count = 1000;

$serv->set(array(
    'max_request' => 0,
    // 'dispatch_mode' => 3,
    'open_length_check' => true,
    'worker_num' => 2,
));


$serv->on('connect', function ($serv, $fd, $from_id) {
    echo "[#" . posix_getpid() . "]\tClient@[$fd:$from_id]: Connect.\n";
});

$serv->on('receive', function (swoole_server $serv, $fd, $from_id, $data) {
    echo "[#" . $serv->worker_id . "]\tClient[$fd]: $data\n";
    if ($serv->send($fd, "hello\n") == false) {
        echo "error\n";
    }
});

$serv->on('close', function ($serv, $fd, $from_id) {
    echo "[#" . posix_getpid() . "]\tClient@[$fd:$from_id]: Close.\n";
});

$serv->on('WorkerStart', function($serv, $worker_id) {
    global $atomic_count, $atomic_time, $per_reponse_count;
    $start_time = time();
    for ($i = 0; $i < $per_reponse_count; $i++) {
        $atomic_count->sub();
        swoole_set_process_name("swooleTestGetRelationsWorker{$worker_id}");
        System::exec_time();
        $client = new Swoolclient("127.0.0.1", 10012, -1, 1000);
        $client->connect();
//type=App&appname=Count&action=index&params=[]
        $params["type"] = "App";
        $params["appname"] = "GetRelations";
        $params["params"] = [
            "company_ids" => [725969, 748405, 722615, 1228473, 1230041, 1228460, 1184453, 1264364, 1121621, 748938, 721738, 1283762, 1166795, 1228964, 1246253, 1121648, 1228459, 1144326, 1118263, 1233045, 1099319, 1230539, 736343, 1088426, 1315185],
            "resume_ids" => [20192,32541,61640,76580,100537,130311,149102,165048,165412,255531,264829,272704,286751,291944,293521,293531,298348,302409,307014,4029779,4038155,4040975,4051919,4058792,4075169,4077982,4080729,4081717,4081742,4099351,4099471,4101095,4101137,4103045,4103788,4103952,4105764,4109231,4109283,4111757,4113514,4114033,4118648,4122471,4126504,4426914,4428412,4429547,4455385,4482237,4510055,4620280,4649805,4657447,4666255,4682684,4693998,4718135,4745037,4755467,4761671,4786417,4793535,4819958,4849031,4850319,4851776,4874193,4879362,4886942,4899114,4904934,4905993,4940359,4941463,4942758,4944402,4946758,4952869,4964365,5060342,5165560,5220515,5223089,5292799,5297486,5380171,5398546,5400945,5427637,5428394,5441753,5445584,5448041,5448312,5449688,5461041,5463640,5485555,5488003]
            ];
        $client->send($params);
        $data = $client->receive();
        $client->close();
        $use_time = System::exec_time();
        $atomic_time->sub($use_time);
        echo PHP_EOL, "[$i] use $use_time ms", PHP_EOL;
    }
    $all_use_time = time() - $start_time;
    if ($atomic_count->get() >= $serv->setting['worker_num'] * $per_reponse_count) {
        echo PHP_EOL, "[$i] qps " . intval($atomic_count->get() / $all_use_time), PHP_EOL;
    }
});
$serv->start();

function __autoload($className) {
    defined('SWOOLE_ROOT_DIR') or define('SWOOLE_ROOT_DIR', (realpath(dirname(__DIR__)) . "/"));
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
