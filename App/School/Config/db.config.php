<?php
/**
 * Created by PhpStorm.
 * User: dongqing.shi
 * Date: 2016/8/8 0008
 * Time: 上午 11:55
 */
//$db['gsystem'] = array(
//    'host' => "192.168.8.102 ",
//    'port' => 3306,
//    'dbms' => 'mysql',
//    'user' => "biuser",
//    'passwd' => "30iH541pSBCU",
//    'name' => "gsystem",
//    'charset' => "utf8",
//    'setname' => true,
//    'persistent' => TRUE, //MySQL长连接
//    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/gsystem_error.sql",
//);
//
//$db['bi'] = array(
//    'host' => "192.168.1.201",
//    'port' => 3306,
//    'dbms' => 'mysql',
//    'user' => "root",
//    'passwd' => "gfd23ghg6CD0f-!if(q4pn",
//    'name' => "bi",
//    'charset' => "utf8",
//    'setname' => true,
//    'persistent' => TRUE, //MySQL长连接
//    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/bi_error.sql",
//);
//
//$db['tobusiness'] = array(
//    'host' => "192.168.1.201",
//    'port' => 3306,
//    'dbms' => 'mysql',
//    'user' => "devuser",
//    'passwd' => "devuser",
//    'name' => "tobusiness",
//    'charset' => "utf8",
//    'setname' => true,
//    'persistent' => TRUE, //MySQL长连接
//    'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/tobusiness_error.sql",
//);

    /*$db['icdc'] = array(
        'host' => "192.168.1.201",
        'port' => 3306,
        'dbms' => 'mysql',
        'user' => "devuser",
        'passwd' => "devuser",
        'name' => "tob_icdc",
        'charset' => "utf8",
        'setname' => true,
        'persistent' => TRUE, //MySQL长连接
        'errorsqlfile' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/icdc_error.sql",
    );*/

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
