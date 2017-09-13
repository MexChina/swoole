<?php
$db['shares'] = array(
    'host' => "127.0.0.1",
    'user' => "root",
    'passwd' => "dongqing",
    'name' => "bi",
    'charset' => "utf8",
    'port' => 3306,
    'debug' => true,
    'setname' => true,
    'persistent' => true, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . "/log/error.sql",
);

$db['toc_grab'] = array(
    'host' => "192.168.1.201",
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "toc_grab",
    'port' => 3306
);

