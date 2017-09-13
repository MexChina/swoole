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
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "log/" . SWOOLE_APP . "/gsystem_error.sql",
);

$db['tobusiness_traffic'] = array(
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "tobusiness_traffic",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/bi_error.sql",
);

$db['gsystem_traffic'] = array(
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "gsystem_traffic",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/bi_error.sql",
);

$db['tobusiness'] = array(
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "tobusiness",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/tobusiness_error.sql",
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
    'db'         => '',
);

