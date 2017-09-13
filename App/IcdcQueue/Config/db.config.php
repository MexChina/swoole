<?php
$db['allot_dev'] = array(
    'host' => "192.168.1.201",
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "icdc_allot",
    'charset' => "utf8",
    'port' => 3306,
    'debug' => true,
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "log/" . SWOOLE_APP . "/error.sql",
);
$db['allot_test'] = array(
    'host' => "10.9.10.6",
    'user' => "icdc",
    'passwd' => "df23453vde",
    'name' => "icdc_allot",
    'charset' => "utf8",
    'port' => 3306,
    'debug' => true,
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "log/" . SWOOLE_APP . "/error.sql",
);
$db['allot_pro'] = array(
    'host' => "192.168.8.109",
    'user' => "tuser",
    'passwd' => "tu7319m",
    'name' => "icdc_allot",
    'charset' => "utf8",
    'port' => 3306,
    'debug' => true,
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "log/" . SWOOLE_APP . "/error.sql",
);