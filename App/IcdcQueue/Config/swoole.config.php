<?php

//服务器配置文件
$swoole = array(
    'timeout' => 2.5, //select and epoll_wait timeout. 
    'worker_num' => 1, //worker process num
    'task_worker_num' => 1,
    'backlog' => 128, //listen backlog
    'max_request' => 5000,
    'daemonize' => 0,
    'dispatch_mode' => 2,
    'log_file' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/swoole_".date("Ymd").".log",
);
