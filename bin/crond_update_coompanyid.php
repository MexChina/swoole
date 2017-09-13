#!/usr/local/php/bin/php
<?php

use Swoole\Core\Lib\SpiderSql;
use Swoole\Core\Lib\Database\MySQLi;
use Swoole\Core\Lib\MyGearmanClient;

error_reporting(E_ERROR);
ini_set('display_errors', 'on');
ini_set('default_charset', 'UTF-8');
ini_set('memory_limit', '1024M');
ini_set('opcache.enable', 0);
date_default_timezone_set('Asia/Shanghai');
define('SWOOLE_ROOT_DIR', (realpath(dirname(__DIR__)) . "/"));


$gm = new MyGearmanClient("icdc_basic");

$mysql_config = array(
    'host' => "p:127.0.0.1",
    'port' => 3306,
    'user' => "root",
    'passwd' => "admin888",
    'name' => "wangkai_data",
);
$db = new MySQLi($mysql_config);

$bi_config = array(
    'host' => "127.0.0.1",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "root",
    'passwd' => "admin888",
    'name' => "resumes_basedata",
    'charset' => "utf8",
    'setname' => true,
    'pre' => 'base_',
    'persistent' => TRUE, //MySQL长连接
);
$savedb = new MySQLi($bi_config);

$result = $db->query("select id, to_id, flag from algo_company_names WHERE `flag` > '0'")->fetchall();
$company_map = [];
foreach ($result as $row) {
    $company_map[$row['id']] = $row['to_id'];
}

$spiderdb = new SpiderSql("127.0.0.1", 10100, SWOOLE_SOCK_SYNC);
$company_ids = implode(",", array_keys($company_map));
$sql = <<<EOF
SELECT * FROM `base_work`  WHERE `company_id`IN($company_ids);
EOF;

$sql = trim($sql);
$result = $spiderdb->connect();
Swoole\Core\Helper\Debug::exec_time();
$spiderdb->query($sql, "resumes_basedata", MYSQLI_STORE_RESULT);
echo "exec time :" . Swoole\Core\Helper\Debug::exec_time() . "\n";
echo "num_rows :" . $spiderdb->num_rows() . "\n";
$resume_ids = [];
$gm_param = array(
    'c' => 'resumes',
    'm' => 'Logic_algorithm',
);
$count_resume_id = $count = 0;
$sql = "REPLACE INTO ";
$field_str = $replace_sql = '';

while ($row = $spiderdb->fetch_assoc()) {
    $count++;
    if (empty($resume_ids[$row['resume_id']])) {
        $count_resume_id++;
        $resume_ids[$row['resume_id']] = 1;
        $gm_param['p'][] = $row['resume_id'];
        if ($count_resume_id >= 1000) {
            $result = $gm->get($gm_param);
            unset($gm_param['p']);
            $count_resume_id = 0;
        }
    }
    $row['company_id'] = $company_map[$row['company_id']];
    if (!$field_str) {
        $field_str = implode(",", array_keys($row));
        $sql .= "($field_str) VALUES ";
    }
    $replace_sql .= "('" . implode("','", $row) . "'),";
    if ($count >= 1000) {
        $result = $savedb->query($sql . trim($replace_sql, ","));
        $replace_sql = "";
        $count = 0;
    }
}

$spiderdb->free_result();

function __autoload($className) {
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
