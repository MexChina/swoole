<?php

namespace Swoole\Core;

use Swoole\Core\Helper\File;
use Swoole\Core\AppServer;

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

    public static function writelog($message) {
        //$ip = String::ip_to_number(gethostbyname($_SERVER['COMPUTERNAME']));
        $file_dir = "default";
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
            $message = date("Y-m-d H:i:s") . "\t$wid" . "\t $message \n";
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

    public static function writenotice($message, $dir = '', $msg_single=1) {
        self::writefile($message, $dir, "notice", $msg_single);
    }

    public static function write_notice($message, $dir = '', $msg_single=1) {
        self::writefile($message, $dir, "notice", $msg_single);
    }

    public static function write_log($message) {
        self::writelog($message);
    }

    public static function write_error($message, $dir = '', $msg_single=1) {
        self::writefile($message, $dir, "error", $msg_single);
    }

    public static function write_alert($message, $dir = '', $msg_single=1) {
        self::writefile($message, $dir, "alert", $msg_single);
    }

    public static function write_info($message, $dir = "", $msg_single=1) {
        self::writefile($message, $dir, "info", $msg_single);
    }

    public static function write_warning($message, $dir = '', $msg_single=1) {
        self::writefile($message, $dir, "warning", $msg_single);
    }

    /*
      $msg_single 是否前面不添加时间等
     */

    private static function writefile($message, $file_dir = "default", $type = "", $msg_single = false) {
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
            $message = $msg_single ? $message : (date("Y-m-d H:i:s") . "\t$wid" . "\t$message \n");
            if (defined("SWOOLE_LOG_DEBUG")) {
                //echo $message;
            }
            try {
                $result = fwrite(self::$file_open[$fkey], $message);
            } catch (Exception $e) {
                echo "写入通知日志文件出错：" . $e->getMessage();
            }
        }
    }

    public static function setLogfile($logfileDir, $logfileName = null) {
        $logfileDir .= SWOOLE_ENVIRONMENT;
        if (!(self::$logfileDir = realpath($logfileDir))) {
            echo "create log file path:" . $logfileDir . "\n";
            File::creat_dir($logfileDir);
            if (!(self::$logfileDir = realpath($logfileDir))) {
                echo ('Logfile dir create failed, file path: ' . $logfileDir);
            }
        }
    }

}
