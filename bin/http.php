<?php

use Swoole\Core\AppServer;
use Swoole\Core\Helper\File;

error_reporting(E_ERROR);
ini_set('display_errors', 'on');
ini_set('default_charset', 'UTF-8');
ini_set('memory_limit', '1024M');
ini_set('opcache.enable', 0);
ini_set('default_socket_timeout', 3);
        
date_default_timezone_set('Asia/Shanghai');
global $_G; //定义全局变量
$_G['server_type'] = "http";
$cmd = $argv[1]; //脚本控制命令
$app = $argv[2]; //应用标识,默认App

$_G["argv"] = [];
foreach ($argv as $value) {
    if (strtolower($value) !== "debug") {
        $_G["argv"][] = $value;
    } else {
        define("SWOOLE_LOG_DEBUG", TRUE);
    }
}

if (!$app) {
    exit("App name is not exist ......\n");
}
define('SWOOLE_ROOT_DIR', (realpath(dirname(__DIR__)) . "/"));
chdir(SWOOLE_ROOT_DIR);
define('SWOOLE_APP', $app);
define('SWOOLE_APP_DIR', SWOOLE_ROOT_DIR . 'App/' . SWOOLE_APP . "/");
if (version_compare(PHP_VERSION, '7.0.0') < 0){
    echo "当前版本:",PHP_VERSION,",请使用PHP7...\n";die;
}

$master_pid_file = SWOOLE_ROOT_DIR . 'App/' . SWOOLE_APP . '/log/Swoole_matser_pid.pid';
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
        echo "start " . SWOOLE_APP . " success ...... \n";
        AppServer::instance()->run();
        break;
    case 'stop':
        $pid = @file_get_contents(MASTER_PID_FILE);
        if (empty($pid)) {
            exit(SWOOLE_APP . " not running?\n");
        }
        stop_and_wait();
        break;
    case 'restart':
        $pid = @file_get_contents(MASTER_PID_FILE);
        if (empty($pid)) {
            echo(SWOOLE_APP . " not running?\n");
            echo "start " . SWOOLE_APP . " success ...... \n";
            AppServer::instance()->run();
            break;
        }
        stop_and_wait();
        echo "start " . SWOOLE_APP . " success ...... \n";
        AppServer::instance()->run();
        break;
    case 'reload':
        if (isset($argv[3])) {
            $pid = intval($argv[3]);
            echo "reload pid:$pid \n";
            $ret = $match = array();
            exec("ps aux | grep -E 'swoole" . SWOOLE_APP . ".+?{$pid}' | grep -v grep", $ret);
            if (preg_match("/^[\S]+\s+(\d+)\s+.+(swoole.+)$/", $ret[0], $match)) {
                $tmp_pid = $match[1];
                $process_name = $match[2];
                if ($tmp_pid) {
                    posix_kill($tmp_pid, SIGTERM);
                    echo "reload $process_name \n";
                    sleep(5);
                    exec("ps aux | grep -E 'swoole" . SWOOLE_APP . ".+?{$pid}' | grep -v grep", $ret);
                    preg_match("/^[\S]+\s+(\d+)\s+.+(swoole.+)$/", $ret[0], $match);
                    $tmp_new_pid = $match[1];
                    if ($tmp_new_pid == $tmp_pid) {
                        //正常结束不了就强制结束
                        posix_kill($tmp_pid, SIGKILL);
                        echo "kill $process_name \n";
                    }
                }
            }
        } else {
            $pid = @file_get_contents(MASTER_PID_FILE);
            if (empty($pid)) {
                exit(SWOOLE_APP . " not running?");
                echo "start " . SWOOLE_APP . " success ...... \n";
                AppServer::instance()->run();
                break;
            }
            posix_kill($pid, SIGUSR1);
            echo "reload " . SWOOLE_APP . "\n";
        }
        break;

    case 'kill':
        echo "kill " . SWOOLE_APP . "\n";
        force_kill();
        force_kill();
        break;
    default:
        echo "Usage: Swooled {start|stop|restart|reload|kill|status}\n";
        exit;
}

function force_kill() {
    $ret = $match = array();
    exec("ps aux | grep -E 'swoole" . SWOOLE_APP . "' | grep -v grep", $ret);
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

function stop_and_wait($wait_time = 5) {
    $pid = @file_get_contents(MASTER_PID_FILE);
    if (empty($pid)) {
        exit("server not running?\n");
    } else {
        echo "send stop signal \n";
        //发送停止信号
        posix_kill($pid, SIGTERM);
        //等待
        sleep($wait_time);
        //查看是否已经结束程序
        if (file_exists(MASTER_PID_FILE)) {
            clearstatcache();
            force_kill();
            force_kill();
            unlink(MASTER_PID_FILE);
        }
        echo "Swoole stoped ...... \n";
    }
}

function __autoload($className) {
    $className = str_replace("\\", "/", $className);
    $className = substr($className, (strpos($className, "/") + 1));
    if (file_exists(SWOOLE_ROOT_DIR . "/$className.php")) {
        require SWOOLE_ROOT_DIR . "/$className.php";
    }
}
