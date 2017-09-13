<?php

//服务器配置文件
$server = array(
    'port' => 10000,
    'tpye' => 'task',
    'logfileDir' => SWOOLE_ROOT_DIR . '/log/',
    'logfileName' => 'server',
    'allowClient' => array(
        "61.160.192.71",
        "61.160.192.72",
        "211.144.216.41",
        "210.73.211.73",
        "61.160.192.27",
        "61.160.254.163",
        "221.228.76.158",
    ),
    'cleanCachetimer' => 60 * 60 * 1000, //过期缓存清理定时任务
);

