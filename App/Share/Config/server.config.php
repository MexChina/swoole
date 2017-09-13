<?php

//服务器配置文件
$server = array(
    'port' => 10001,
    'logfileDir' => SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . '/log/',
    'logfileName' => 'server',
    'share_dir' => "/dev/shm/swoole", //共享目录，用户储存需要在进程间中转的文件与大包
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

