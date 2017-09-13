<?php

namespace Swoole\Core;

use Swoole\Core\Helper\File;
use Swoole\Core\AppServer;
use \SeasLog;

/**
 * Description of log
 *
 * @author zhouxuelin
 */
class Log {

    private static $logfileDir = "";
    private static $logfileName = "";
    private static $file_open = array();
    private static $logfilehash = array();

    public static function writelog($message,$file_dir='default') {
        $wid = defined("SWOOLE_WORKER_ID") ? SWOOLE_WORKER_ID : "";
        if ($message) {
            $date = date("Y_m_d_H");
            $logfile = self::$logfileDir . "/{$file_dir}/{$date}_{$wid}_info.log";
            $fkey = $file_dir . $wid;
            if (empty(self::$logfilehash[$fkey]) || self::$logfilehash[$fkey] != $logfile) {
                File::creat_dir_with_filepath($logfile);
                if (!empty(self::$file_open[$fkey])) {
                    fclose(self::$file_open[$fkey]);
                }
                self::$file_open[$fkey] = fopen($logfile, "a+");
                self::$logfilehash[$fkey] = $logfile;
            }
            $message = date("Y-m-d H:i:s") . "\t$wid" . "\t$Client $message \n";
            if (defined("SWOOLE_LOG_DEBUG")) {
                echo $message;
            }
            try {
                $result = fwrite(self::$file_open[$fkey], $message);
            } catch (Exception $e) {
                echo "写入系统日志文件出错：" . $e->getMessage();
            }
        }
    }

    public static function writenotice($message, $dir = '') {
        self::writefile($message, $dir, "notice");
    }

    public static function write_notice($message, $dir = '') {
        self::writefile($message, $dir, "notice");
    }

    public static function write_log($message) {
        self::writelog($message);
    }

    public static function write_error($message, $dir = '') {
        self::writefile($message, $dir, "error");
    }

    public static function write_alert($message, $dir = '') {
        self::writefile($message, $dir, "alert");
    }

    public static function write_info($message, $dir = "") {
        self::writefile($message, $dir, "info");
    }

    public static function write_warning($message, $dir = '') {
        self::writefile($message, $dir, "warning");
    }

    private static function writefile($message, $file_dir = "default", $type = "") {
        $wid = defined("SWOOLE_WORKER_ID") ? SWOOLE_WORKER_ID : "";
        if ($message) {
            $date = date("Y_m_d_H");
            $logfile = self::$logfileDir . "/{$file_dir}/{$date}_{$wid}_{$type}.log";
            $fkey = $file_dir . $wid;
            if (empty(self::$logfilehash[$fkey]) || self::$logfilehash[$fkey] != $logfile) {
                File::creat_dir_with_filepath($logfile);
                if (!empty(self::$file_open[$fkey])) {
                    fclose(self::$file_open[$fkey]);
                }
                self::$file_open[$fkey] = fopen($logfile, "a+");
                self::$logfilehash[$fkey] = $logfile;
            }
            $message = date("Y-m-d H:i:s") . "\t$wid" . "\t$message \t";

            try {
                fwrite(self::$file_open[$fkey], $message);
            } catch (\Exception $e) {
                echo "写入通知日志文件出错：" . $e->getMessage();
            }
        }
    }

    public static function setLogfile($logfileDir, $logfileName = null) {
        if (!(self::$logfileDir = realpath($logfileDir))) {
            echo ('Logfile Dir not found, file path: ' . $logfileDir);
        }
    }

    public static function cleanLogfile() {
        $logfilepath = self::$logfileDir . "/" . date("Y") . "/";
        if (realpath($logfilepath)) {
            $result = File::del_dir($logfilepath);
            unlink(self::$logfileDir . "/" . "swoole.log");
            if ($result) {
                Log::writelog("日志文件（path:{$logfilepath}）清理完毕......");
            }
        }
        //清理缓存
        $cachepath = SWOOLE_ROOT_DIR . "/cache/";
        if (realpath($cachepath)) {
            $result = File::del_dir($cachepath);
            if ($result) {
                Log::writelog("日志文件（path:{$cachepath}）清理完毕......");
            }
        }
    }

}
