<?php

use Swoole\Core\Lib\AsyncMysql;

error_reporting(E_ALL);
define('SWOOLE_ROOT_DIR', realpath(dirname(__DIR__)) . "/");
$config = array(
    'host' => "127.0.0.1",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "root",
    'passwd' => "admin888",
    'name' => "resume_source_data",
    'charset' => "utf8",
    'setname' => true,
    'pre' => 'base_',
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "log/error.sql",
);

$db = new AsyncMysql($config);
$count = 0;
$total_count = 1;
global $count;
sleep(12);
Swoole\Core\Helper\System::exec_time();
for ($i = 1; $i <= $total_count; $i++) {
    $db->query("select * from base_common limit 10", function($dbresult) use ($i) {
        global $total_count;
//        foreach ($dbresult as $key => $value) {
//            var_export($value);
//        }
        echo "\n";
        echo "num_rows:" . count($dbresult) . ",count:" . $i . "\n";
        unset($dbresult);
        if ($i == $total_count) {
            echo Swoole\Core\Helper\System::exec_time() . "\n";
        }
    });
}

//$db = new Swoole\Core\Lib\Database($config);
//$db->connect();
//Swoole\Core\Helper\System::exec_time();
//for ($i = 1; $i <= 10000; $i++) {
//    $db->query("select * from base_common limit 10");
//}
//echo Swoole\Core\Helper\System::exec_time() . "\n";

function db($dbconfig) {
    $db = new Database($dbconfig);
    $db->connect();
    return $db;
}

function __autoload($className) {
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
