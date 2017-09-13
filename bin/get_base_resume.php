
<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

error_reporting(E_ALL);
ini_set('memory_limit', '3G');
define('SWOOLE_ROOT_DIR', realpath(dirname(__DIR__)) . "/");
define('SWOOLE_APP', "ResumeEtl");

$ID = $argv[1];
$dbhost = [0 => "192.168.8.109", 1 => "192.168.8.112"];
$tableid = ($ID % 8) + floor($ID / 40000000) * 8;
$dbhost = $dbhost[intval($tableid % 2)];
echo "tableid = {$tableid} \n";
$dbconfig = array(
    'host' => $dbhost,
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_" . $tableid,
    'charset' => "utf8"
);
$db = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['passwd'], $dbconfig['name'], $dbconfig['port']);
if ($argv[2]) {
    $sql = "SELECT  r.work_experience AS work_experience,UNIX_TIMESTAMP(r.resume_updated_at) as resume_updated_at, ra.*, re.compress FROM resumes AS r LEFT JOIN resumes_algorithms AS ra ON ra.id=r.id LEFT JOIN resumes_extras AS re ON re.id=r.id WHERE r.id='{$ID}'";
    $result = $db->query($sql);
    $resumes_data = $result->fetch_all(MYSQLI_ASSOC)[0];
    $resumes_data["compress"] = bin2hex($resumes_data["compress"]);
    $out = "<?php\n\$resume_data = " . var_export($resumes_data, TRUE) . ";\n";
    $file_path = SWOOLE_ROOT_DIR ."bin/resume_test_data.php";
    $result = \Swoole\Core\Helper\File::write_file($file_path, $out);
    var_dump($file_path);
}
$result = $db->query("select compress from resumes_extras where id = $ID");
$tmp = $result->fetch_all(MYSQLI_ASSOC);
foreach ($tmp as $tmpid => $tmpdata) {
    $data = json_decode(gzuncompress($tmpdata['compress']), true);
    echo "tar_len:" . strlen(gzuncompress($tmpdata['compress'])) . "\n";
    var_export($data);echo"\n";
}

function __autoload($className) {
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
