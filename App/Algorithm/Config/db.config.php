<?php
/**
 * Created by PhpStorm.
 * User: jiqing.sun
 * Date: 2017-02-15
 * Time: 下午 17:04
 */
$db['allot'] = array(
    'host' => "192.168.1.201",
    'port' => 3306,
    'dbms' => 'mysql',
    'user' => "devuser",
    'passwd' => "devuser",
    'name' => "icdc_allot",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_allot_error.sql",
);

for($i=0;$i<24;$i++){
    $db['icdc_'.$i] = array(
        'host' => "192.168.1.201",
        'port' => 3306,
        'dbms' => 'mysql',
        'user' => "devuser",
        'passwd' => "devuser",
        'name' => "icdc_".$i,
        'charset' => "utf8",
        'setname' => true,
        'persistent' => false, //MySQL长连接
        'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
    );
}

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
