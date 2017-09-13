<?php

use Swoole\Core\Lib\Database;

$db['icdc'] = array(
    'type' => Database::TYPE_MYSQLi,
    'host' => "192.168.8.105",
    'port' => 3307,
    'dbms' => 'mysql',
    'user' => "kdd",
    'passwd' => "kd12934d",
    'name' => "icdc_0",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => FALSE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "/cache/sql/error.sql",
);

$db['master'] = array(
    'type' => Database::TYPE_MYSQLi,
    'host' => "14.29.87.224",
    'port' => 3306,
    'dbms' => 'mysql',
    'engine' => 'TokuDB',
    'user' => "root",
    'passwd' => "32w8KviyX9HJs7D",
    'name' => "datacenter",
    'charset' => "utf8",
    'setname' => true,
    'pre' => 'dc_',
    'persistent' => FALSE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "/cache/sql/error.sql",
);

//基础数据库配置
$db['basedatadb'] = array(
    'type' => Database::TYPE_MYSQLi,
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "icdc_0",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "/cache/sql/error.sql",
);

//基础数据库配置
$db['gsystem'] = array(
    'type' => Database::TYPE_MYSQLi,
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "gsystem",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "/cache/sql/error.sql",
);

//bi数据集市
$db['bi'] = array(
    'type' => Database::TYPE_MYSQLi,
    'host' => "localhost",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "root",
    'passwd' => "admin888",
    'name' => "newbi",
    'charset' => "utf8",
    'setname' => true,
    'pre' => 'base_',
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "/cache/sql/error.sql",
);
