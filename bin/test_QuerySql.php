#!/usr/local/php/bin/php
<?php

use Swoole\Core\Lib\SpiderSql;

error_reporting(E_ERROR);
ini_set('display_errors', 'on');
ini_set('default_charset', 'UTF-8');
ini_set('memory_limit', '1024M');
ini_set('opcache.enable', 0);
date_default_timezone_set('Asia/Shanghai');


define('SWOOLE_ROOT_DIR', (realpath(dirname(__DIR__)) . "/"));

$spiderdb = new SpiderSql("127.0.0.1", 10100, SWOOLE_SOCK_SYNC);

$sql = <<<EOF
INSERT INTO my_count.quarter_come_go  SELECT * FROM(SELECT IFNULL(gc.pcompany_id, bw.company_id) AS company_id, bw.resume_id, bw.`level`, bw.`region_id`, IFNULL(bf.function_id, 0) AS function_id, bc.`degree`, bc.`age`, bc.`work_experience`, IFNULL(IFNULL(gc1.pcompany_id, bw1.company_id), 0) AS `ocompany_id` FROM base_work AS bw LEFT JOIN bi_gsystem.`company_smapping` AS gc ON gc.company_id=bw.company_id AND gc.depth=1 LEFT JOIN base_work AS bw1 ON bw1.sort_id = bw.sort_id - 1 AND bw1.resume_id = bw.resume_id LEFT JOIN bi_gsystem.`company_smapping` AS gc1 ON gc1.company_id=bw1.company_id AND gc1.depth=1 LEFT JOIN base_function AS bf ON bf.wid = bw.wid LEFT JOIN base_common AS bc ON bc.resume_id = bw.resume_id WHERE bw.`start_time` >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 12 MONTH)) AND bw.company_id > 0)AS t1 WHERE t1.company_id <> t1.ocompany_id
EOF;



$sqls = preg_split("/\;\n/", $sql);
foreach ($sqls as $sql) {
    $sql = trim($sql);
    $result = $spiderdb->connect();
//for ($i = 1; $i <= 10; $i++) {
    Swoole\Core\Helper\Debug::exec_time();
    $spiderdb->query($sql, "resume_source_data", MYSQLI_USE_RESULT);
    echo "exec time :" . Swoole\Core\Helper\Debug::exec_time() . "\n";
    echo "num_rows :" . $spiderdb->num_rows() . "\n";


//$spiderdb->query($sql);
//while ($row = $spiderdb->fetch_assoc()) { var_export($row);}

    $spiderdb->free_result();
}

//}

function __autoload($className) {
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
