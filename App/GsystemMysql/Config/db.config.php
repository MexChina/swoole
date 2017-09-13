<?php

$db['gsystem'] = array(
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "gsystem",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/gsystem_error.sql",
);

$db['new_gsystem'] = array(
    'host' => "192.168.1.66",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "root",
    'passwd' => "bi123456",
    'name' => "gsystem",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/new_gsystem_error.sql",
);
