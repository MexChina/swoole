<?php
/**
 * 主库 写
 */
for($i=0;$i<24;$i++){
    $db['master_icdc_'.$i] = array(
        'host' => "192.168.1.201",
        'port' => 3306,
        'dbms' => 'mysql',
        'user' => "devuser",
        'passwd' => "devuser",
        'name' => "icdc_".$i,
        'charset' => "utf8",
        'setname' => true,
        'persistent' => TRUE, //MySQL长连接
        'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
    );
}

/**
 * 从库 读
 */
for($i=0;$i<24;$i++){
    $db['slave_icdc_'.$i] = array(
        'host' => "192.168.1.201",
        'port' => 3306,
        'dbms' => 'mysql',
        'user' => "devuser",
        'passwd' => "devuser",
        'name' => "icdc_".$i,
        'charset' => "utf8",
        'setname' => true,
        'persistent' => TRUE, //MySQL长连接
        'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
    );
}
/**
 * 刷库 助理表
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
    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
);