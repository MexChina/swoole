#!/usr/local/php/bin/php

<?php

use Swoole\App\ResumeEtl\Jdcount;

error_reporting(E_ERROR);
ini_set('display_errors', 'on');
ini_set('default_charset', 'UTF-8');
ini_set('memory_limit', '512M');
ini_set('opcache.enable', 0);
date_default_timezone_set('Asia/Shanghai');
define('SWOOLE_ROOT_DIR', realpath(dirname(__DIR__)) . "/");
define('SWOOLE_APP', "ResumeEtl");

$jcount = new Jdcount();
$jcount->docount($argv[1]);

//$filter_monthly[] = array("id" => 3, "title" => "5k-10k", "min" => "5_", "max" => "_10");
//$filter_monthly[] = array("id" => 4, "title" => "10-15k", "min" => "10_", "max" => "_15");
//$filter_monthly[] = array("id" => 5, "title" => "15-20k", "min" => "15_", "max" => "_20");
//$filter_monthly[] = array("id" => 6, "title" => "20-30k", "min" => "20_", "max" => "_30");
//$filter_monthly[] = array("id" => 7, "title" => "30-50k", "min" => "30_", "max" => "_50");
//
//
//$keyword = "php";
//$gm = new GearmamClient("edps");
//foreach ($filter_monthly as $monthly_pay_filter) {
//    $search = array(
//        "keyword" => $keyword,
//        "m" => 'jd',
//        "detail" => '1',
//        "sort" => "field_lastdatesort_desc,field_score,id_desc",
//        "start" => 0,
//        "count" => 10,
//        "status" => 0,
//        "facet" => "off",
//        "resort" => 1,
//        "updated_sequence" => (time() - 30 * 86400) . "000000000_",
//        "salary_lower" => $monthly_pay_filter["min"],
//        "salary_upper" => $monthly_pay_filter["max"],
//    );
//    $param = array('c' => '',
//        'm' => '',
//        'p' => $search,
//    );
//    print_r($gm->get($param));
//}

function __autoload($className) {
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
