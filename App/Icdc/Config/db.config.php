<?php

for($i=0;$i<7;$i++){
    $db['icdc_'.$i] = array(
        'host' => "192.168.1.201",
        'port' => 3306,
        'dbms' => 'mysql',
        'user' => "devuser",
        'passwd' => "devuser",
        'name' => "icdc_".$i,
        'charset' => "utf8",
        'setname' => true,
        'persistent' => TRUE, //MySQL长连接
        'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
    );
}

for($i=0;$i<7;$i++){
    $db['new_icdc_'.$i] = array(
        'host' => "192.168.1.66",
        'port' => 3306,
        'dbms' => 'mysql',
        'user' => "root",
        'passwd' => "bi123456",
        'name' => "icdc_".$i,
        'charset' => "utf8",
        'setname' => true,
        'persistent' => TRUE, //MySQL长连接
        'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/new_icdc_error.sql",
    );
}

for($i=0;$i<7;$i++){
    $db['algorithm_'.$i] = array(
        'host' => "192.168.1.66",
        'port' => 3306,
        'dbms' => 'mysql',
        'user' => "root",
        'passwd' => "bi123456",
        'name' => "algorithm_".$i,
        'charset' => "utf8",
        'setname' => true,
        'persistent' => TRUE, //MySQL长连接
        'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/new_icdc_error.sql",
    );
}

//old allot
$db['icdc_allot'] = array(
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "icdc_allot",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
);

$db['new_icdc_allot'] = array(
    'host' => "192.168.1.66",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "root",
    'passwd' => "bi123456",
    'name' => "icdc_allot",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
);