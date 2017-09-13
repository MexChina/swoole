#!/usr/local/php/bin/php
<?php

use Swoole\Core\AppServer;
use Swoole\Core\Helper\File;
use Swoole\Core\App;
use Swoole\Core\Lib\Swoolclient;

error_reporting(E_ERROR);
ini_set('display_errors', 'on');
ini_set('default_charset', 'UTF-8');
ini_set('memory_limit', '1024M');
ini_set('opcache.enable', 0);
date_default_timezone_set('Asia/Shanghai');
global $_G; //定义全局变量
$resume_id = $argv[1];
$app = $argv[2]; //应用标识
$i = 0;

$params = array();
while (true) {
    $i++;
    if (isset($argv[$i + 2])) {
        $params[$i] = $argv[$i + 2];
    } else {
        break;
    }
}
unset($i);
$cmd = $cmd ? $cmd : 'start';
$app = $app ? $app : "ResumeEtl"; //定义默认App
if (!$app) {
    exit("App name is not exist ......\n");
}

define('SWOOLE_ROOT_DIR', (realpath(dirname(__DIR__)) . "/"));
$client = new Swoolclient("127.0.0.1", 10001);
$result = $client->connect(function() use($client, $resume_id) {
    $data["action"] = "sp_resumes";
    $data["params"] = ["resume_id" => $resume_id ? $resume_id : 0];
    $client->send($data, function($data) use($client) {
        var_export($data);
    });
});

//$db_bi = array(
//    'host' => "127.0.0.1",
//    'port' => 3306,
//    'dbms' => 'mysql',
//    'user' => "root",
//    'passwd' => "admin888",
//    'name' => "resume_source_data",
//    'charset' => "utf8",
//    'setname' => true,
//    'pre' => 'base_',
//    'persistent' => TRUE, //MySQL长连接
//    'errorsqlfile' => SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . "/log/error.sql",
//);
//
//$db = new \Swoole\Core\Lib\Database($db_bi);
//$result_select_db = $db->select_db("newbi1");
////$result = $db->query("SELECT * FROM information_schema.columns WHERE table_name='base_function'");
////$rows = $result->result->fetch_all(MYSQLI_ASSOC);
////$field_type = [];
////
////$types = array(
////    8 => 'bigint',
////    253 => 'varchar',
////    1 => 'tinyint',
////    9 => 'mediumint',
////    3 => 'int',
////);
////foreach ($rows as $row) {
////    $field_type[$row['COLUMN_NAME']] = $row['DATA_TYPE'];
////}
//
//
//$result = $db->query("select * from resume_source_data.base_work limit 100");
//$reslut = $result->result;
//$fields = $result->result->fetch_fields();
//$field_list = [];
//foreach ($fields as $key => $field) {
//    $field_list[$key] = $field->name;
//}
//$datas = $result->result->fetch_all();
//
//
//var_export($fields);
//
//
//exit;
//$function = function () use ($spiderdb) {
//    //$sql = "SELECT f.function_id as function_id,c.salary_range_id as salary_range_id,c.degree as degree,c.workex_range_id as workex_range_id,w.basic_salary as basic_salary, 0 AS region_id from base_function f inner JOIN base_work w on f.wid=w.wid  INNER JOIN base_common c on w.resume_id=c.resume_id  where w.so_far=1";
//
//    $sql = "SELECT bf.* FROM (SELECT resume_id FROM base_work WHERE company_id IN (736447,739451,744772,753688,757058,758828,775994,778273,798988,1010158,1025394,1030063,1121477,1121665,1121666,1121667,1121668,1121669,1121670,1121671,1126202,1166801,1166821,1174607,1190916,1216492,1228319,1228332,1228423,1228444,1234083,1237274,1239394,1243502,1243804,1244486,1245316,1247481,1250843,1271787)) AS t LEFT JOIN base_function AS bf ON t.resume_id = bf.resume_id WHERE bf.function_id>0";
//    Swoole\Core\Helper\Debug::exec_time();
//    $spiderdb->query($sql, function() use ($spiderdb) {
//        echo "exec time :" . Swoole\Core\Helper\Debug::exec_time() . "\n";
//        while ($row = $spiderdb->fetch_assoc()) {
//            var_export($row);
//        }
//    }, true);
//};
//$result = $spiderdb->connect($function);

function __autoload($className) {
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
