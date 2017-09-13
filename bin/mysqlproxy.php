#!/usr/local/php/bin/php

<?php

use Swoole\Core\MysqlProxy;
use Swoole\Core\Helper\File;

error_reporting(E_ERROR);
ini_set('display_errors', 'on');
ini_set('default_charset', 'UTF-8');
ini_set('memory_limit', '512M');
ini_set('opcache.enable', 0);
date_default_timezone_set('Asia/Shanghai');



if (empty($argv[1])) {
    echo "Usage: Swooled {start|stop|restart|reload|kill|status}" . PHP_EOL;
    exit;
}
$cmd = $argv[1];
//$cmd = 'start';
define('SWOOLE_ROOT_DIR', realpath(dirname(__DIR__) . "/"));

chdir(SWOOLE_ROOT_DIR);

if (!version_compare(PHP_VERSION, '5.3.0', '>=')) {
    exit("Swoole PHP >= 5.3.0 required \n");
}

$master_pid_file = SWOOLE_ROOT_DIR . '/log/MysqlProxy_matser_pid.pid';
define('MASTER_PID_FILE', $master_pid_file);
//创建日志文件目录
$logfielpath = SWOOLE_ROOT_DIR . "/log/";
if (!is_dir($logfielpath)) {
    File::creat_dir($logfielpath);
}
//检查pid对应的进程是否存在，不存在删除PID文件
if ($cmd != 'status' && is_file(MASTER_PID_FILE)) {
    //检查权限
    if (!posix_access(MASTER_PID_FILE, POSIX_W_OK)) {
        if ($stat = stat(MASTER_PID_FILE)) {
            if (($start_pwuid = posix_getpwuid($stat['uid'])) && ($current_pwuid = posix_getpwuid(posix_getuid()))) {
                exit("\n\033[31;40mSwoole is started by user {$start_pwuid['name']}, {$current_pwuid['name']} can not $cmd Swoole, Permission denied\033[0m\n\n\033[31;40mSwoole $cmd failed\033[0m\n\n");
            }
        }
        exit("\033[31;40mCan not $cmd Swoole, Permission denied\033[0m\n");
    }
    //检查pid进程是否存在
    if ($pid = @file_get_contents(MASTER_PID_FILE)) {
        if (false === posix_kill($pid, 0)) {
            if (!unlink(MASTER_PID_FILE)) {
                exit("\033[31;40mCan not $cmd Swoole\033[0m\n\n");
            }
        }
    }
}

switch ($cmd) {
    case 'start':
        echo "start swoole success ...... \n";
        $mysqlproxy = new MysqlProxy();
        $mysqlproxy->run();
        break;
    case 'stop':
        $pid = @file_get_contents(MASTER_PID_FILE);
        if (empty($pid)) {
            exit("Swoole not running?\n");
        }
        stop_and_wait();
        break;
    case 'restart':
        stop_and_wait();
        echo "start swoole success ...... \n";
        $mysqlproxy = new MysqlProxy();
        $mysqlproxy->run();
        break;
    case 'reload':
        $pid = @file_get_contents(MASTER_PID_FILE);
        if (empty($pid)) {
            exit("\033[33;40mSwoole not running?\033[0m\n");
        }
        posix_kill($pid, SIGHUP);
        echo "reload Swoole\n";
        break;
    case 'kill':
        force_kill();
        force_kill();
        break;

    default:
        echo "Usage: Swooled {start|stop|restart|reload|kill|status}\n";
        exit;
}

function force_kill() {
    $ret = $match = array();
    exec("ps aux | grep -E 'mysqlProxy' | grep -v grep", $ret);
    $this_pid = posix_getpid();
    echo "this pid $this_pid \n";
    $this_ppid = posix_getppid();
    echo "this parent pid $this_ppid \n";
    foreach ($ret as $line) {
        if (preg_match("/^[\S]+\s+(\d+)\s+/", $line, $match)) {
            $tmp_pid = $match[1];
            if ($this_pid != $tmp_pid && $this_ppid != $tmp_pid) {
                $return = posix_kill($tmp_pid, SIGKILL);
                if ($return) {
                    echo "kill pid $tmp_pid \n";
                }
            }
        }
    }
}

function stop_and_wait($wait_time = 3) {
    $pid = @file_get_contents(MASTER_PID_FILE);
    if (empty($pid)) {
        //exit("server not running?\n");
    } else {
        $start_time = time();
        echo "send kill signal \n";
        posix_kill($pid, SIGINT);
        while (is_file(MASTER_PID_FILE)) {
            clearstatcache();
            usleep(1000);
            if (time() - $start_time >= $wait_time) {
                force_kill();
                force_kill();
                unlink(MASTER_PID_FILE);
                usleep(500000);
                break;
            }
        }
        echo "MysqlProxy stoped ...... \n";
    }
}

function __autoload($className) {
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
