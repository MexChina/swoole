<?php

$db['icdc_map'] = array(
    'host' => "10.9.10.6",
    'port' => 3308,
    'dbms' => 'mysql',
    'user' => "icdc",
    'passwd' => "df23453vde",
    'name' => "icdc_map",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "log/" . SWOOLE_APP . "/error.sql",
);



$db['memcache'] = array(
    'server'=>array(array('10.9.10.6',11211)),
    'config'=>array(
        'prefix'=>'',
        'timeout'=>''
    )
);
