<?php

use Swoole\Core\Lib\Thrift\HbaseConn;
use Swoole\Core\Lib\Database\MySQLi;
use Swoole\Core\Helper\System;

//00008c47-130310-5-1405
//echo hexdec("8c47");
//exit();
ini_set('memory_limit', '1024M');
spl_autoload_register("autoload", TRUE, false);

$mysql_config = array(
    'host' => "p:127.0.0.1",
    'port' => 3306,
    'user' => "root",
    'passwd' => "admin888",
    'name' => "icdc_0",
);

$startid = 0;
$linepertime = 500;
$db = new Swoole\Core\Lib\AsyncMysql($mysql_config, 3);
$hbase = new HbaseConn("RESUME_DATA");
$field = [];
query();

function query($startid = 0) {
    global $linepertime, $db;
    $sql = "SELECT re.compress, r.id,UNIX_TIMESTAMP(r.resume_updated_at) as resume_updated_at FROM resumes AS r  LEFT JOIN resumes_extras AS re ON re.id=r.id WHERE r.id>=(SELECT id FROM resumes where is_deleted='N' LIMIT {$startid},1) AND is_deleted='N' LIMIT {$linepertime}";
    $db->query($sql, function($result) use($startid) {
        global $field, $hbase, $linepertime;
        query($startid + $linepertime);
        System::exec_time();
        $column_pre = "info";
        //System::exec_time();
        $puts = [];
        $diff_fields = ["is_private", "cv_tag", "cv_title", "first_trade_list", "second_trade_list", "company_info", "must", "should"];
        while ($row = $result->fetch_assoc()) {
            $rowkey = "";
            $resume_id = $row['id'];
            $resume_hex_id = str_pad(dechex($resume_id), 8, "0", STR_PAD_LEFT);
            $resume = json_decode(gzuncompress($row['compress']), true);
            $tmp_cvalue = [];
            foreach ($resume as $key => $c_value) {
                if (empty($c_value) || !is_array($c_value)) {
                    continue;
                }
                $c_sort = 0;
                if (!in_array($key, $field)) {
                    $field[] = $key;
                }
                if (!is_array(array_values($c_value)[0])) {
                    $c_value = array($c_value);
                }
                $tmp_ccvalue = [];
                foreach ($c_value as $c_id => $cc_value) {
                    $date = date("ymd", $row['resume_updated_at']);
                    $key_num = array_search($key, $field);
                    $rowkey = "$resume_hex_id-$date-$key_num-$c_sort";
                    $c_sort++;
                    $columns = [];
                    foreach ($cc_value as $vkey => $v) {
                        if (in_array($vkey, $diff_fields)) {
                            continue;
                        }
                        if (is_string($v)) {
                            $v = trim($v);
                            if ($v === "0.0" || $v === "0" || $v === "" || $v === 0) {
                                continue;
                            }
                        } elseif (is_array($v)) {
                            if (empty($v)) {
                                continue;
                            }
                        }
                        $columns[$vkey] = $v;
                    }
                    $tmp_ccvalue[$c_id] = $columns;
                    //$hbase->deleteAllRow($rowkey,"");
                }
                $tmp_cvalue[$key] = $tmp_ccvalue;
            }
            $tmp_cvalue['resume_id'] = $resume_id;
            Swoole\Core\Helper\File::write_file(SWOOLE_ROOT_DIR."/bin/test_0.log", json_encode($tmp_cvalue)."\n","a");
        }
        
        unset($tmp_cvalue);
        echo "$startid-" . ($startid + $linepertime) . " use " . System::exec_time() . " ms, memory use " . System::get_used_memory() . "\n";
        var_export($field);
        echo"\n";
    }, MYSQLI_USE_RESULT);
}

function autoload($className) {
    if (!defined("SWOOLE_ROOT_DIR")) {
        define('SWOOLE_ROOT_DIR', (realpath(dirname(__DIR__)) . "/"));
    }
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
