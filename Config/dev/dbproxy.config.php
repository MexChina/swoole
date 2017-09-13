<?php

$dbproxy = array();
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_0",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_1",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_2",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_3",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_4",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_5",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_6",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_7",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_8",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_9",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_10",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_11",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_12",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_13",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_14",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_15",
    'pre' => '',
);
$dbproxy['db']['icdc_0'] = array(
    'host' => "192.168.8.105",
    'port' => 3307,
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_16",
    'pre' => '',
);

$dbproxy['db']['newbi'] = array(
    'host' => "localhost",
    'port' => 3306,
    'user' => "rsdata",
    'passwd' => "rsdata0824",
    'name' => "resumes_basedata",
    'pre' => 'base_',
);
$dbproxy['db']['newbi'] = array(
    'host' => "localhost",
    'port' => 3306,
    'user' => "rsdata",
    'passwd' => "rsdata0824",
    'name' => "resumes_basedata",
    'pre' => 'base_',
);
$dbproxy['db']['newbi'] = array(
    'host' => "localhost",
    'port' => 3306,
    'user' => "rsdata",
    'passwd' => "rsdata0824",
    'name' => "resumes_basedata",
    'pre' => 'base_',
);
$dbproxy['db']['newbi'] = array(
    'host' => "localhost",
    'port' => 3306,
    'user' => "rsdata",
    'passwd' => "rsdata0824",
    'name' => "resumes_basedata",
    'pre' => 'base_',
);









$dbproxy['server'] = array(
    'port' => 13306,
    'logfileDir' => SWOOLE_ROOT_DIR . '/log/',
    'logfileName' => 'dbproxy'
);

$dbproxy['swoole'] = array(
    'timeout' => 0.5, //select and epoll_wait timeout. 
    'worker_num' => 1, //worker process num
    'task_worker_num' => 0,
    'backlog' => 128, //listen backlog
    'max_request' => 0,
    'daemonize' => 0,
    'dispatch_mode' => 2,
    'log_file' => SWOOLE_ROOT_DIR . '/log/mysqlProxy.log',
);
