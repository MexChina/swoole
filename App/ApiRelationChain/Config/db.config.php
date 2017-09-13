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

$db['gsystem'] = array(
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "gsystem",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/gsystem_error.sql",
);

/**
 * PostgreSql
 */
$db['bi_data'] = array(
    'host' => "221.228.230.200",
    'user' => "gpadmin",
    'passwd' => "gpadmin123456",
    'name' => "ifchange_dw",
    'port' => 5432,
    'charset' => "utf8",
);

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

