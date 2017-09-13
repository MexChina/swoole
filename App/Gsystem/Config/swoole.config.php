<?php

$swoole = array(
    'timeout' => 2.5, //select and epoll_wait timeout. 
    'worker_num' => 16, //worker process num  1cpu*4
    'reactor_num' => 0,
    'task_worker_num' => 0,
    'backlog' => 128, //listen backlog
    'max_request' => 5000,
    'daemonize' => 1,
    'dispatch_mode' => 2,
    'log_file' => SWOOLE_ROOT_DIR."log/".SWOOLE_APP."/swoole_".date("Ymd").".log",
);
