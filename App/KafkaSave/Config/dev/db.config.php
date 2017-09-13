<?php

$db['icdc_map'] = array(
    'host' => "192.168.1.66",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "icdc_map",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
);


$db['icdc'] = array(
    'host' => "192.168.1.66",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "icdc_map",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => false, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
);


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

for($i=0;$i<5;$i++){
    $db['icdc_'.$i] = array(
        'host' => "192.168.1.201",
        'port' => 3310,
        'dbms' => 'mysql',
        'user' => "devuser",
        'passwd' => "devuser",
        'name' => "icdc_$i",
        'charset' => "utf8",
        'setname' => true,
        'persistent' => TRUE, //MySQL长连接
        'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
    );
}



$db['memcache'] = array(
    'server'=>array(array('192.168.1.108',11211)),
    'config'=>array(
        'prefix'=>'',
        'timeout'=>''
    )
);
