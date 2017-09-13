<?php

use Swoole\Core\Lib\Database;

$db['api'] = array(
    'host' => "127.0.0.1",
    'port' => 3306,
    'user' => "root",
    'passwd' => "dongqing",
    'name' => "bi",
    'charset' => "utf8",
    'debug' => true,
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "log/" . SWOOLE_APP . "/bi_error.sql",
);

