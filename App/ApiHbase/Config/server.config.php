<?php

//服务器配置文件
$server = array(
    'port' => 10003,
    'logfileDir' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP.'/',
    'logfileName' => 'server',
    'share_dir' => "/dev/shm/swoole", //共享目录，用户储存需要在进程间中转的文件与大包
    'allowClient' => array(),
    'cleanCachetimer' => 60 * 60 * 1000, //过期缓存清理定时任务
);

