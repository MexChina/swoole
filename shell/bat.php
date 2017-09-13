#!/usr/bin/env php
<?php
/*
 * PHP CLI shell 多进程入口
 *
 * 运行 ./bat.php [--help] 查看帮助
 * 运行 ./bat.php bat-test.php 执行示例
 *
 * bat-test.php 脚本内容如下：
 * <?php
 
#防误确认
if(!bat::confirm()){
    bat::message("用户取消");
    exit;
}
 
#全局变量
global $x;
 
$x = 12345;
 
#添加任务
bat::run('a');
bat::run('b', __LINE__);
bat::run('c');
bat::run('b', __LINE__);
bat::run('a');
 
#启动任务
bat::start();
 
#任务函数
function a(){
    global $x;
    do{
        bat::notify("我是通知主进程显示的提示文字，测试变量 \$x = " . $x++);
        usleep(500000);
    }while(mt_rand(100, 999) > 159);
}
 
function b($line){
    do{
        bat::notify("我是显示传递的参数 \$line = $line");
        usleep(500000);
    }while(mt_rand(100, 999) > 359);
}
 
function c(){
    global $x;
    bat::notify("多个任务之间的初始变量值不受影响， \$x = $x");
    bat::notify("我是暂停 9 秒时间测试");
    sleep(9);
    bat::notify("我是出错代码 5 测试");
    exit(5);
}
 
 * ?>
 */
 
/** 确保这个脚本只能运行在 SHELL 中 */
if(substr(php_sapi_name(), 0, 3) !== 'cli'){
    die("This Programe can only be run in CLI mode.\n");
}
 
if(!is_callable('pcntl_fork') || !is_callable('msg_send')){
    bat::message("本程序需要 pcntl, sysvmsg 扩展，但您的系统没有安装！", 2);
    exit(5);
}
 
class bat{
    static private
            $max = 3,
            $total = 0,
            $running = 0,
            $failure = 0,
            $finished = 0,
            $tasks = array(),
            $msg, $msgs = array(),
            $logfile = "/tmp/bat.php.log",
            $childs, $get, $parent,
            $start, $split;
 
    static function main(){
        $i = 1;
        $files = array();
        if($_SERVER["argc"] > 1){
            while($i < $_SERVER["argc"]){
                switch($_SERVER["argv"][$i++]){
                    case "?":
                    case "/?":
                    case "-?":
                    case "-h":
                    case "--help":
                        self::usage();
                    case "-f":
                    case "--file":
                        $file = $_SERVER["argv"][$i++];
                        if(is_readable($file)){
                            $files[] = $file;
                            continue;
                        }
                        if(is_null($file)){
                            self::message("缺少脚本参数", 1);
                            help(1);
                        }else{
                            self::message("脚本 $file 不在在或不可访问", 2);
                            exit(4);
                        }
                    case "-m":
                    case "--max":
                        if(self::$max = $_SERVER["argv"][$i++]){
                            self::$max = intval(self::$max);
                            if(self::$max >= 1){
                                continue;
                            }
                            self::message("进程数量应为正整数", 2);
                            exit(8);
                        }
                        self::message("未指定进程数量", 2);
                        exit(7);
                    case "-l":
                    case "--log":
                    case "--logfile":
                        if(self::$logfile = $_SERVER["argv"][$i++]){
                            if(is_dir(self::$logfile)){
                                self::$logfile .= "/bat.php.log";
                            }
                            if(is_file(self::$logfile)){
                                if(is_writable(self::$logfile)){
                                    continue;
                                }
                            }else{
                                if(is_writable(dirname(self::$logfile))){
                                    continue;
                                }
                            }
                            self::message("日志目录不可写", 2);
                            exit(9);
                        }
                    case "-v":
                    case "--version":
                        exit(self::version());
                    default :
                        $file = $_SERVER["argv"][$i - 1];
                        if(is_readable($file)){
                            $files[] = $file;
                            continue;
                        }
                        self::message("脚本 $file 不在在或不可访问", 2);
                        exit(4);
                }
            }
 
            set_time_limit(0);
            error_reporting(8106 & E_ALL);
            ini_set('display_errors', 'Off');
            set_error_handler(array(__CLASS__, 'error'), E_ALL);
            set_exception_handler(array(__CLASS__, 'exception'));
            register_shutdown_function(array(__CLASS__, 'shutdown'));
 
            self::$start = time();
            self::$split = str_repeat('=', 512);
            self::$parent = msg_get_queue(getmypid());
 
            foreach($files as $file){
                self::inc($file);
            }
 
            self::end();
            exit;
        }
 
        self::usage();
    }
 
    static function run($fun, $arg = null){
        if(is_callable($fun)){
            self::$tasks[] = array($fun, $arg);
        }else{
            throw new Exception("不是函数或不可调用", 9);
        }
    }
 
    static function start(){
        self::$total = count(self::$tasks);
        foreach(self::$tasks as $fun_arg){
            if(self::$max < ++self::$running){
                self::run_wait();
            }elseif(self::$running == 1){
                # 清屏并设置光标到第一行
                $x = intval(`tput lines`);
                echo str_repeat("\n", $x -1);
                self::flush('程序开始执行...', 1);
            }
            if($cid = pcntl_fork()){
                if($cid < 0){
                    throw new Exception("创建进程失败", 3);
                }
                self::$childs[$cid] = msg_get_queue($cid);
            }else{
                ob_start();
                self::$tasks = array();
                self::$get = msg_get_queue(getmypid());
                self::$msg = sprintf("%-6d", getmypid());
                msg_send(self::$parent, 1, getmypid(), false);
                call_user_func($fun_arg[0], $fun_arg[1]);
                exit;
            }
        }
        while(self::$running) self::run_wait();
        self::$tasks = array();
    }
 
    static private function run_wait(){
        $nomsg_interval = time();
label_wait:
        if(msg_receive(self::$parent, 0, $typ, 8192, $msg, false, MSG_NOERROR | MSG_IPC_NOWAIT)){
            if($typ != 3){
                if($typ == 1){
                    $msg = sprintf("%-6d%s %s", $msg, date("H:i:s"), '进程启动');
                }elseif($typ == 4){
label_child_exit:
                    unset(self::$childs[pcntl_waitpid($msg, &$status)]);
                    if(!pcntl_wifexited($status) || pcntl_wexitstatus($status)){
                        $msg = sprintf("%-6d%s %s", $msg, date("H:i:s"), '进程异常退出');
                        if(pcntl_wifexited($status)){
                            $msg .= '，错误代码：' . pcntl_wexitstatus($status);
                        }
                        self::$failure++;
                    }else{
                        $msg = sprintf("%-6d%s %s", $msg, date("H:i:s"), '进程执行完毕');
                        self::$finished++;
                    }
                    self::flush($msg, $nomsg_interval);
                    self::$running--;
                    return;
                }else{
                    goto label_wait;
                }
            }
            $nomsg_interval = time();
            self::flush($msg, $nomsg_interval);
        }else{
            if($nomsg_interval != time()){
                foreach(self::$childs as $msg => $t){
                    if(!msg_queue_exists($msg)){
                        goto label_child_exit;
                    }
                }
                echo "\33[0;0H"; $lines = intval(`tput lines`);
                echo "\33[K运行时长：", self::run_time(), ' ', date("Y-m-d H:i:s", self::$start), ' - ', date("Y-m-d H:i:s"), "\33[$lines;0H";
            }
            usleep(100000);
        }
        goto label_wait;
    }
 
    static function notify($msg){
        msg_send(self::$parent, 3, self::$msg . date("H:i:s ") . $msg, false);
    }
 
    static function message($msg, $code = 0){
        switch($code){
        case 0:
            echo "\33[37m提示：\33[0m", $msg, "\n";
            break;
        case 1:
            echo "\33[33m警告：\33[0m", $msg, "\n";
            break;
        case 2:
            echo "\33[31m错误：\33[0m", $msg, "\n";
            break;
        }
    }
 
    static function confirm($msg = "确定要继续执行"){
        echo $msg, "(yes/no)？: "; # 暂这样
        return "yes\n" == fgets(STDIN);
    }
 
    static function help($code = 0){
        echo "\n请使用 $_ENV[_] --help 查看帮助！\n";
        $code && exit($code);
    }
 
    static function usage(){
        $bat = __CLASS__;
 
        echo ""
        , "Usage:\n"
        , " $_ENV[_] [options] [-f | --file] <file>\n"
        , "Options:\n"
        , " -h | --help     显示本帮助信息\n"
        , " -v | --version      查看程序版本信息\n"
        , "\n"
        , " -m | --max <num>  同时执行进程数量，默认 ", self::$max, " 个\n"
        , " -l | --log <file> 错误记录日志文件，默认 ", self::$logfile, "\n"
        , "Information:\n"
        , " 脚本中调用 $bat::run(fun[, arg]) 来添加任务\n"
        , " fun 为要执行的函数名；arg 为传递给这个函数的参数，可省\n"
        , "\n"
        , " 脚本中调用 $bat::start() 来启动子进程执行上面添加的任务\n"
        , " 在子进程中，通过调用 $bat::notify(msg) 发送要显示的信息给父进程\n"
        , " 在子进程中，程序执行发生错误，要让主进程统计为失败需用 exit(num) 非零返回\n"
        ;
        exit;
    }
 
    static function version(){
        return "Version: 0.1 by huye\n";
    }
 
    static private function inc($file){
        include $file;
    }
 
    static private function end(){
        $cols = intval(`tput cols`);
        $lines = intval(`tput lines`);
 
        if(self::$total){
            self::flush("执行完毕.", 1);
            echo "\33[$lines;{$cols}H\33[1C\n\n";
        }
 
        if(is_file(self::$logfile))echo "\33[K发生错误：", self::$logfile, "\n";
        echo "\33[K运行时长：", self::run_time(), ' ', date("Y-m-d H:i:s", self::$start), ' - ', date("Y-m-d H:i:s"), "\n";
        echo "\33[K执行完毕：已完成任务 ", self::$finished, " 个", self::$failure ? "，失败 " . self::$failure . " 个" : "", self::$total ? "（共 " . self::$total . " 个）" : "", "。\n";
    }
 
    static private function flush($msg, $time){
        $cols = intval(`tput cols`);
        $lines = intval(`tput lines`);
        if($msg){
            $_max = $cols;
            foreach(explode("\n", $msg) as $msg){
                if($cols < strlen($msg)){# ascii utf8 ascii utf8 ascii ...
                    $tmp = preg_split("#((?:[\xe0-\xef][\x80-\xbf]{2})+)#", $msg, 0, PREG_SPLIT_DELIM_CAPTURE);
                    for($i = 0, $l = count($tmp); $i < $l;){
                        $x = strlen($z = $tmp[$i]);
                        if($_max > $x){
                            $_max -= $x;
                            if(++$i >= $l)break;
                            $x = strlen($z = $tmp[$i]) / 3 * 2;
                            if($_max > $x){
                                $_max -= $x;
                                $i++; continue;
                            }elseif($_max < $x){
                                $_max = floor($_max / 2) * 3;
                                $msg = array_slice($tmp, $i -1);
                                $msg[0] = '';
                                $msg[1] = substr($z, $_max);
                                $tmp[$i] = substr($z, 0, $_max);
                            }else{
                                $msg = array_slice($tmp, $i + 1);
                            }
                        }elseif($_max < $x){
                            $msg = array_slice($tmp, $i);
                            $msg[0] = substr($z, $_max);
                            $tmp[$i] = substr($z, 0, $_max);
                        }else{
                            $msg = array_slice($tmp, $i);
                            $msg[0] = '';
                        }
                        if(++$i < $l){
                            array_splice($tmp, $i);
                        }
                        if(isset($msg[1])){
                            self::$msgs[] = implode("", $tmp);
                            $msg[0] = "               " . $msg[0];
                            $i = 0; $l = count($msg); $tmp = $msg; $_max = $cols;
                        }elseif(isset($msg[0]) && strlen($msg[0])){
                            self::$msgs[] = implode("", $tmp);
                            if($cols - 15 < strlen($msg[0])){
                                foreach(str_split($msg[0], $cols - 15) as $tmp){
                                    $tmp = "               " . $tmp;
                                    if($cols == strlen($tmp)){
                                        self::$msgs[] = $tmp;
                                    }else{
                                        $tmp = array($tmp);
                                        break;
                                    }
                                }
                            }else{
                                $tmp = array("               " . $msg[0]);
                            }
                            break;
                        }else{
                            break;
                        }
                    }
                    self::$msgs[] = implode("", $tmp);
                }else{
                    self::$msgs[] = $msg;
                }
            }
        }else{
            self::$msgs[] = $msg;
        }
 
        static $last_time = 0;
        if($last_time == $time)return true;
        $last_time = $time; # 防止在远程 ssh 的时候刷屏死掉
 
        echo "\33[0;0H";
#       echo "\33[K程序信息：", self::version();
        echo "\33[K运行时长：", self::run_time(), ' ', date("Y-m-d H:i:s", self::$start), ' - ', date("Y-m-d H:i:s"), "\n";
 
        if($lines < 5){
            if($lines < 3) return;
        }else{
            echo $split = substr(self::$split, 0, $cols), "\n";
 
            if(($_max = count(self::$msgs) + 4) > $lines){
                array_splice(self::$msgs, 0, $_max - $lines);
            }elseif($lines > $_max){
                $split = str_repeat("\n\33[K", $lines - $_max) . $split;
            }
 
            echo "\33[K", implode("\n\33[K", self::$msgs), "\n", $split, "\n";
        }
 
        $msg = "已完成任务 " . self::$finished . " 个";
        if(self::$failure) $msg .= "，失败 " . self::$failure . " 个";
        if(self::$total) $msg .= "（共 " . self::$total . " 个）";
        echo str_repeat(' ', $cols - strlen(preg_replace("#[\xe0-\xef][\x80-\xbf]{2}#", "**", $msg))), $msg, "\33[$lines;0H";
    }
 
    static function run_time(){
        $consume = time()
                 - self::$start;
 
        $str = "";
        if($consume >= 86400){
            $str = floor($consume / 86400) . "天";
            $consume = $consume % 86400;
            $zero = true;
        }
        if($consume >= 3600){
            $str .= floor($consume / 3600) . "时";
            $consume = $consume % 3600;
            $zero = true;
        }elseif($consume > 0 && isset($zero)){
            unset($zero);
            $str .= "零";
        }
        if($consume >= 60){
            $str .= floor($consume / 60) . "分";
            $consume = $consume % 60;
            $zero = true;
        }elseif($consume > 0 && isset($zero)){
            unset($zero);
            $str .= "零";
        }
        if($consume > 0){
            $str .= $consume . "秒";
        }elseif($str == ""){
            $str = "0秒";
        }
 
        return $str;
    }
 
    static function error($no, $err, $file, $line){
        if(error_reporting()){
            $log = $no & 1032 ? 'M' : ($no & 514 ? 'W' : ($no & 2048 ? 'M' : 'E'));
            $log = "[" . date("m-d H:i:s") . "] $log $line $file $err\n";
            file_put_contents(self::$logfile, $log, FILE_APPEND);
        }
    }
 
    static function shutdown(){
        if($last = error_get_last() and 85 & $last['type']){
            self::error($last['type'], $last['message'], $last['file'], $last['line']);
            self::$get || self::end();
        }
        if(self::$get){
            msg_send(self::$parent, 4, getmypid(), false); # 通知父进程结束
            self::$parent = self::$get; # 同时也防止上一行通知失败
            ob_end_clean();
        }
        msg_remove_queue(self::$parent);
    }
 
    static function exception($e){
        self::error($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
        exit($e->getCode());
    }
}
 
bat::main();