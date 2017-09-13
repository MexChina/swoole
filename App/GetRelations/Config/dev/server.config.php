<?php

//服务器配置文件
$server = array(
    'port' => 10012,
    //'worker_heart_time' => 600, //进程心跳检测时间
    'logfileDir' => SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . '/log/',
    'share_dir' => "/dev/shm/swoole", //共享目录，用户储存需要在进程间中转的文件与大包
    'allowClient' => array(
        "192.168.206.2",
    ),
    'cleanCachetimer' => 60 * 60 * 1000, //过期缓存清理定时任务
);

