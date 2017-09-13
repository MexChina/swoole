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

    public static function write_log($message, $client = array()) {
        $message = self::format_msg($message, $client);
        if (defined("SWOOLE_LOG_DEBUG")) {
            echo date("Y-m-d H:i:s", time()) . "\t" . $message . "\n";
        }
        try {
            $result = SeasLog::debug($message);
        } catch (Exception $e) {
            echo "write log error:" . $e->getMessage();
        }
    }

    public static function writelog($message, $client = array()) {
        self::write_log($message);
    }

    public static function write_notice($message, $dir = '') {
        $message = self::format_msg($message);
        if (defined("SWOOLE_LOG_DEBUG")) {
            //echo date("Y-m-d H:i:s", time()) . "\t" . $message . "\n";
        }
        try {
            if ($dir) {
                $result = SeasLog::notice($message, [], $dir);
            } else {
                $result = SeasLog::notice($message);
            }
        } catch (Exception $e) {
            echo "write log error:" . $e->getMessage();
        }
    }

    public static function writenotice($message) {
        self::write_notice($message);
    }

    public static function write_error($message, $dir = '') {
        $message = self::format_msg($message);
        if (defined("SWOOLE_LOG_DEBUG")) {
            //echo date("Y-m-d H:i:s", time()) . "\t" . $message . "\n";
        }
        try {
            if ($dir) {
                $result = SeasLog::error($message, [], $dir);
            } else {
                $result = SeasLog::error($message);
            }
        } catch (Exception $e) {
            echo "write log error:" . $e->getMessage();
        }
    }

    public static function write_alert($message, $dir = '') {
        $message = self::format_msg($message);
        if (defined("SWOOLE_LOG_DEBUG")) {
            //echo date("Y-m-d H:i:s", time()) . "\t" . $message . "\n";
        }
        try {
            if ($dir) {
                $result = SeasLog::alert($message, [], $dir);
            } else {
                $result = SeasLog::alert($message);
            }
        } catch (Exception $e) {
            echo "write log error:" . $e->getMessage();
        }
    }

    public static function write_info($message, $dir = "") {
        $message = self::format_msg($message);
        if (defined("SWOOLE_LOG_DEBUG")) {
            //echo date("Y-m-d H:i:s", time()) . "\t" . $message . "\n";
        }
        try {
            if ($dir) {
                $result = SeasLog::info($message, [], $dir);
            } else {
                $result = SeasLog::info($message);
            }
        } catch (Exception $e) {
            echo "write log error:" . $e->getMessage();
        }
    }

    public static function write_warning($message, $dir = '') {
        $message = self::format_msg($message);
        if (defined("SWOOLE_LOG_DEBUG")) {
            echo date("Y-m-d H:i:s", time()) . "\t" . $message . "\n";
        }
        try {
            if ($dir) {
                $result = SeasLog::warning($message, [], $dir);
            } else {
                $result = SeasLog::warning($message);
            }
        } catch (Exception $e) {
            echo "write log error:" . $e->getMessage();
        }
    }

    public static function setBasePath($logfileDir) {
        if (!(self::$logfileDir = realpath($logfileDir))) {
            exit('Logfile Dir not found, file path: ' . $logfileDir);
        }
        SeasLog::setBasePath(self::$logfileDir);
        self::$logfileName = self::$logfileName ? self::$logfileName : "server";
    }

    public static function setLogger($app = "") {
        if ($app) {
            SeasLog::setLogger($app);
        }
    }

    public static function flushBuffer() {
        SeasLog::flushBuffer();
    }

    private static function format_msg($message, array $client = []) {
        if ($message) {
            $wid = defined("SWOOLE_WORKER_ID") ? SWOOLE_WORKER_ID : "";
            if (!empty($client)) {
                $Client_info = $client['remote_ip'] . ":" . $client['remote_port'] .
                        ( (isset($client['commond']) && $client['commond']) ? " commond:" . $client['commond'] : "") .
                        "\t";
            } else {
                $Client_info = "";
            }
            $Client_info = $Client_info ? "$Client_info\t" : "";
            $wid_info = $wid !== "" ? "$wid\t" : "";
            $message = "{$wid_info}{$Client_info}{$message}";
        }
        return $message;
    }

}
