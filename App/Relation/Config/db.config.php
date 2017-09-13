<?php


/**
 * MySql
 */
$db['bi'] = array(
    'host' => "192.168.1.201",
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "api",
    'charset' => "utf8",
    'port' => 3306,
    'debug' => true,
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/api_error.sql",
);

/**
 * PostgreSql
 */
$db['bi_data'] = array(
    'host' => "221.228.230.200",
    'user' => "user",
    'passwd' => "user0526",
    'name' => "bi_data_dev",
    'charset' => "utf8",
    'port' => 8066,
    'debug' => true,
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/api_error.sql",
);
$db['bi_gsystem'] = array(
    'host' => "221.228.230.200",
    'user' => "rsdata",
    'passwd' => "rsdata0824",
    'name' => "bi_gsystem",
    'charset' => "utf8",
    'port' => 3307,
    'debug' => true,
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/api_error.sql",
);

//$db['bi_data'] = array(
//    'host' => "221.228.230.200",
//    'user' => "rsdata",
//    'passwd' => "rsdata0824",
//    'name' => "bi_data_dev",
//    'charset' => "utf8",
//    'port' => 3307,
//    'debug' => true,
//    'setname' => true,
//    'persistent' => true, //MySQL长连接
//    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/api_error.sql",
//);


/**
 * Redis
 */
$db['redis'] = array(
    'host'       => '192.168.1.108',
    'port'       => 6379,
    'password'   => 'a2f2*^*a4fe',
    'timeout'    => 0,
    'expire'     => 0,
    'persistent' => false,
    'prefix'     => '',
    'db'         => 5,
);

