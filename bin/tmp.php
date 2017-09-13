<?php

use Swoole\Core\Lib\Database\MySQLi;
use Swoole\Core\Helper\System;
use Swoole\Core\Helper\File;

echo date("y-m-d H:i:s", strtotime("today"));
exit;
ini_set('memory_limit', '1024M');
$len = 0;
while (true) {
    $str = "21333333333333333333333333333333333333333333333333333333333333333333dfs121sadf32sa1\n";
    $len += strlen($str);
    //File::write_file("21333333333333333333333333333333333333333333333333333333333333333333dfs121sadf32sa1", "/opt/log/default/test.log");
    //swoole_async_write("/opt/log/default/test.log","21333333333333333333333333333333333333333333333333333333333333333333dfs121sadf32sa1",$len);
    $seasLog = new SeasLog();
    $seasLog->log(SEASLOG_INFO, $str);
    unset($seasLog);
    echo "memory used " . System::get_used_memory() . "  strlen:$len\n";
}
exit;



$fp = fsockopen("www.baidu.com", 80, $errno, $errstr, 1);
$r = stream_set_timeout($fp, 0, 1);
if (!$fp) {
    echo "$errstr ($errno)<br />\n";
} else {
    $out = "GET / HTTP/1.1\r\n";
    $out .= "Host: www.baidu.com\r\n";
    $out .= "Connection: Close\r\n\r\n";
    $r = fwrite($fp, $out);
}

$sock = swoole_event_add($fp, function($fp) {
    global $sock;
    $r = swoole_event_del($fp);
    while (!feof($fp)) {
        echo fgets($fp, 128);
    }
    fclose($fp);
});
return;
$resume_id = 984615861;
$resume_hex_id = str_pad(dechex($resume_id), 8, "0", STR_PAD_LEFT);
echo $resume_hex_id . "\n";
echo hexdec("3ab00bb5");
exit;

$mysql_config = array(
    'host' => "p:127.0.0.1",
    'port' => 3306,
    'user' => "root",
    'passwd' => "admin888",
    'name' => "icdc_0",
);
$handle_count = 1000;
$startid = 0;
$linepertime = 2000;
$sql = "SELECT r.work_experience AS work_experience, r.user_id, UNIX_TIMESTAMP(r.resume_updated_at) as resume_updated_at, ra.* FROM resumes AS r LEFT JOIN resumes_algorithms AS ra ON ra.id=r.id LEFT JOIN resumes_extras AS re ON re.id=r.id WHERE r.id>=(SELECT id FROM resumes where is_deleted='N' LIMIT {$startid},1) AND is_deleted='N' LIMIT {$linepertime}";
$mydb = new MySQLi($mysql_config);
$mydb->async_query($sql, function($result) use($mydb, $sql) {
    var_export($result);
    $mydb->async_query($sql, function($result) {
        var_export($result);
    });
});



exit;
System::exec_time();
echo "1 memory used " . System::get_used_memory() . "\n";
for ($i = 0; $i < $handle_count; $i++) {
    $result = $mydb->query($sql, MYSQLI_USE_RESULT);
    $result->free();
}
echo "2 memory used " . System::get_used_memory() . " TIME:" . System::exec_time() . "\n";
$time = time();
$work_num = 20;
$db = new Swoole\Core\Lib\AsyncMysql($mysql_config, $work_num);
$ii = 0;
$close = false;
for ($iii = 0; $iii < $work_num; $iii++) {
    test_query($db, $sql);
}

function test_query($db, $sql) {
    global $ii, $handle_count, $close, $time;
    if ($ii < $handle_count) {
        $db->query($sql, function($result) use ($db, $sql, $ii) {
            //echo "async_$ii memory used " . System::get_used_memory() . "\n";
            test_query($db, $sql);
        }, MYSQLI_ASYNC);
        $ii++;
    } elseif (!$close) {
        $close = TRUE;
        $db->close();
        echo "async memory used " . System::get_used_memory() . " TIME:" . (time() - $time) . " time:" . System::exec_time() . "\n";
    }
}

class test {

    private $db;

    function __construct(array $mysql_config) {
        $this->db = new MySQLi($mysql_config);
        $this->db->connect();
        //$r = swoole_event_add($this->db->sock, array($this, "check_rsync_dbback"));
    }

    function __destruct() {
        unset($this->db);
    }

    function query($sql) {
        return $this->db->query($sql);
    }

    function as_query($sql) {
        return $this->db->query($sql, MYSQLI_ASYNC);
    }

    function a_query($sql) {
        echo "1 memory used " . System::get_used_memory() . "\n";
        $obj = $this;
        $r = $this->db->async_query($sql, function(Swoole\Core\Lib\Database\MySQLiRecord $result) use ($obj, $sql) {
            $insert_sql = "INSERT INTO test VALUES ";
            //$test = $result->fetch_all(MYSQLI_NUM);
            $fields = $result->result->fetch_fields();
            $field_list = [];
            foreach ($fields as $key => $field) {
                $field_list[$key] = $field->name;
            }
            if (!$insert_sql) {
                $insert_sql = "INSERT INTO `test` ";
            }
            $insert_sql = $insert_sql . "(`" . implode("`,`", $field_list) . "`) VALUES ";

            while ($row = $result->fetch_assoc()) {
                $tmp_values_sql = "";

                //Log::write_log("[server_name:$server_name] region__count:$region__count handle_region:$handle_region insert_sql:$insert_sql......");
                foreach ($row as $value) {
                    $value = $obj->db->real_escape_string($value);
                    $tmp_values_sql = $tmp_values_sql !== "" ? "$tmp_values_sql," : "";
                    $tmp_values_sql .= $value ? "'$value'" : "0";
                }
                $insert_sql .= "(" . $tmp_values_sql . "),";
            }
            $insert_sql = trim($insert_sql, ",");
            $r = $obj->db->async_query($insert_sql, function(Swoole\Core\Lib\Database\MySQLiRecord $result) use($obj, $sql) {
                echo "5 memory used " . System::get_used_memory() . "\n";
                if ($result->result) {
                    $obj->a_query($sql);
                    echo "write success !!!!!!!!!!!!!!! \n";
                }
                unset($obj);
                unset($sql);
            });
            echo "time used " . System::exec_time() . "\n";
            echo "2 memory used " . System::get_used_memory() . "\n";
            unset($result);
            unset($obj);
            unset($sql);
            //var_export($result->fetch_all(MYSQLI_ASSOC));
        }, MYSQLI_USE_RESULT);
        echo "4 memory used " . System::get_used_memory() . "\n";
        echo "query exec $r \n";
    }

    function check_rsync_dbback($dbsock) {
        $db = $links[] = MySQLi::$MYSQLI_DBS[$dbsock];
        echo "check_rsync_dbback run ......\n";
        if (!mysqli_poll($links, $links, $links, 0, 1000)) {
            echo("mysqli error($db->errno):$db->error \n");
//            $r = swoole_event_del($db->sock);
//            $r = $db->checkConnection();
            return;
        }
        @$mresult = $db->reap_async_query();
        if ($mresult) {
            var_export($mresult);
        } else {
            echo("mysqli error($db->errno):$db->error \n");
            if ($db->errno == 2013 || $db->errno == 2006 || $db->errno == 1053) {
                $sock = $db->sock;
                $r = swoole_event_del($db->sock);
                $r = $db->checkConnection();
                if ($r === true) {
                    return;
                }
            } else {
                Log::write_error(sprintf("[{$this->db}-{$this->workerid}]cmysql MySQLi Error[{$errno}]: %s ......", substr($error, 0, 200)));
            }
            return;
        }
    }

}

function create_mysql_connect($mysql_config) {
    $mysqli = mysqli_init();
    if (!$mysqli) {
        Log::write_error('mysqli_init failed');
        return false;
    }
    if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3)) {
        Log::write_error('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
        return false;
    }

    $host = $mysql_config["host"];
    $prot = $mysql_config["port"];
    $username = $mysql_config["user"];
    $password = $mysql_config["passwd"];
    $dbname = $mysql_config["name"];
    if (!$host || !$prot || !$username || !$password || !$dbname) {
        Log::write_error("error:mysql config  exception\thost:$host\tprot:$prot\tusername:$username\tpassword:$password\tdbname:$dbname");
        return false;
    }
    if (!$mysqli->real_connect($host, $username, $password, $dbname, $prot)) {
        Log::write_error('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        return false;
    }
    $mysqli->sock = swoole_get_mysqli_sock($mysqli);
    return $mysqli;
}

function __autoload($className) {
    if (!defined("SWOOLE_ROOT_DIR")) {
        define('SWOOLE_ROOT_DIR', (realpath(dirname(__DIR__)) . "/"));
    }
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}

function test() {
    $mysql_config = array(
        'host' => "p:127.0.0.1",
        'port' => 3306,
        'user' => "root",
        'passwd' => "admin888",
        'name' => "icdc_0",
    );
    $db = new MySQLi($mysql_config);
    $db->connect();
    $_i = 0;
    echo "1 memory used " . $a = System::get_used_memory() . "\n";
    $function = function() use ($db) {
        
    };
    echo "1-1 memory used " . $a1 = System::get_used_memory() . "\n";
    echo "function diff =" . ($a1 - $a) . "\n";
    //echo "all memory used " . $a = System::get_used_memory() . "\n";
    for ($i = 0; $i < 10; $i++) {

        //echo "$i-1 memory used " . $a = System::get_used_memory() . "\n";
//        echo "$i-1-1 memory used " . $a1 = System::get_used_memory() . "\n";
        try {
            $r = swoole_event_add($db->sock, "Swoole\Core\Lib\Database\MySQLi::mysqli_async_callback");
        } catch (Error $exc) {
            echo $exc->getTraceAsString();
        }


//        echo "$i-2 memory used " . $a2 = System::get_used_memory() . "\n";
//        echo "add diff =" . ($a2 - $a1) . "\n";
        $r = swoole_event_del($db->sock);
//        echo "$i-2 memory used " . $a3 = System::get_used_memory() . "\n";
//        echo "del diff =" . ($a3 - $a2) . "\n";
        swoole_event_exit();
        unset($r);
        echo "$i-3 memory used " . $b = System::get_used_memory() . "\n";
        echo "diff =" . ($b - $_i) . "\n";
        $_i = $b;
    }
    $db->close();
    unset($db);
}

function test1($sock) {
    echo "test1    sock:$sock " . System::get_used_memory() . "\n";
    global $db;
    $links[] = $db;
    if (!mysqli_poll($links, $links, $links, 0, 1000)) {
        //swoole_event_del($db->sock);
        return;
    }
    @$mresult = $db->reap_async_query();
    var_dump($mresult);
    if (!$mresult) {
        echo "errno:$db->errno, error:$db->error\n";
        //swoole_event_del($db->sock);
    }
}

function test2($sock) {
    global $db;
    @$mresult = $db->reap_async_query();
    echo "test2    sock:$sock " . System::get_used_memory() . "\n";
    var_dump($mresult);
}
