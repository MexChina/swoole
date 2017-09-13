<?php

namespace Swoole\Core\Helper;

class System {
    private static $exec_time=0;

//获取系统cpu的空闲百分比
    public static function get_cpufree() {
        $cmd = "top -n 1 -b -d 0.1 | grep 'Cpu'"; //调用top命令和grep命令
        $lastline = exec($cmd, $output);

        preg_match('/(\S+)%id/', $lastline, $matches); //正则表达式获取cpu空闲百分比
        $cpufree = round($matches[1], 2);
        return $cpufree;
    }

//获取内存空闲百分比
    public static function get_memfree() {
        $cmd = 'free -m'; //调用free命令
        $lastline = exec($cmd, $output);
        preg_match('/Mem:\s+(\d+)/', $output[1], $matches);
        $memtotal = $matches[1];
        preg_match('/(\d+)$/', $output[2], $matches);
        $memfree = round($matches[1] * 100.0 / $memtotal, 2);
        return $memfree;
    }

//获取当前脚本的内存占用
    public static function get_used_memory() {
        $m = memory_get_usage();
        $m = $m ? $m : self::memory_get_used();
        $unit = array('B', 'K', 'M', 'G', 'T');
        return @round($m / pow(1024, ($i = floor(log($m, 1024)))), 2) . $unit[$i];
    }

//获取某个进程的内存占用情况
    public static function memory_get_used($pid = 0) {
        $pid = $pid ? $pid : getmypid();
        if (PATH_SEPARATOR !== ':') {
            exec('tasklist /FI "PID eq ' . $pid . '" /FO LIST', $output);
            return preg_replace('/[^0-9]/', '', $output[5]) * 1024;
        } else {
            exec("ps -eo%mem,rss,pid | grep $pid", $output);
            $output = explode(" ", trim($output[0]));
            return $output[1] * 1024;
        }
    }

//获取某个程序当前的进程数
    public static function get_proc_count($name) {
        $cmd = "ps -e"; //调用ps命令
        $output = shell_exec($cmd);

        $result = substr_count($output, ' ' . $name);
        return $result;
    }

//计算代码执行时间
    public static function exec_time() {
        $start_time = 0;
        if (self::$exec_time) {
            $start_time = self::$exec_time;
        }
        self::$exec_time = microtime(true);
        return $start_time ? intval((self::$exec_time - $start_time) * 1000) : 0;
    }

}
