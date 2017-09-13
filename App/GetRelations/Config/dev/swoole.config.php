<?php

//服务器配置文件
$swoole = array(
    'timeout' => 2.5, //select and epoll_wait timeout. 
    'worker_num' => 2, //worker process num
    'task_worker_num' => 4,
    'backlog' => 128, //listen backlog
    'max_request' => 0,
    'daemonize' => 0,
    'dispatch_mode' => 2,
    'task_tmpdir' =>"/dev/shm/",
    'log_file' => SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . "/log/swoole.log",
);
