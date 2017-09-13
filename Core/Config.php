<?php

namespace Swoole\Core;

/**
 * 
 * 配置
 * @author zhouxuelin
 *
 */
class Config {

    /**
     * 配置数据
     * @var array
     */
    private $config = array();

    /**
     * 单例模式
     * @var instance of Config
     */
    protected static $instances = null;

    /**
     * 构造函数
     * @throws \Exception
     */
    private function __construct() {
        $env = SWOOLE_ENVIRONMENT;
        //这里主要是兼容以前的项目，如果为生产环境会自动向加载根目录下面的配置文件
        if ($env == 'pro') {
            $config_dir = SWOOLE_ROOT_DIR . "/Config/";
            if (!is_dir($config_dir)) {
                echo('Configuration dir not found');
            }
            foreach (glob($config_dir . '*.config.php') as $config_file) {
                $configname = basename($config_file, '.config.php');
                @include $config_file;
                if (!empty($$configname)) {
                    $this->config[$configname] = $$configname;
                }
            }
            $app_config_dir = SWOOLE_ROOT_DIR . 'App/' . SWOOLE_APP . "/Config/";
            foreach (glob($app_config_dir . '*.config.php') as $app_config_file) {
                $configname = basename($app_config_file, '.config.php');
                @include $app_config_file;
                if (!empty($$configname)) {
                    $this->config[$configname] = empty($this->config[$configname]) ? array() : $this->config[$configname];
                    $this->config[$configname] = array_merge($this->config[$configname], $$configname);
                }
            }
        }

        $config_dir = SWOOLE_ROOT_DIR . "/Config/{$env}/";
        if (!is_dir($config_dir)) {
            echo("$app_config_dir not found \n");
        }
        foreach (glob($config_dir . '*.config.php') as $config_file) {
            $configname = basename($config_file, '.config.php');
            @include $config_file;
            if (!empty($$configname)) {
                $this->config[$configname] = $$configname;
            }
        }
        $app_config_dir = SWOOLE_ROOT_DIR . 'App/' . SWOOLE_APP . "/Config/{$env}/";
        if (!file_exists($app_config_dir)) {
            echo("$app_config_dir not found, please check your env config \n");
        }
        foreach (glob($app_config_dir . '*.config.php') as $app_config_file) {
            $configname = basename($app_config_file, '.config.php');
            @include $app_config_file;
            if (!empty($$configname)) {
                $this->config[$configname] = empty($this->config[$configname]) ? array() : $this->config[$configname];
                $this->config[$configname] = array_merge($this->config[$configname], $$configname);
                //echo "$configname=>". var_export($this->config[$configname], TRUE)."\n";
            }
        }
    }

    /**
     * 只加载某个文件，并返回数据
     * @return array
     */
    private static function get_configs($file_name) {
        $new_config = [];
        $env = SWOOLE_ENVIRONMENT;
        //这里主要是兼容以前的项目，如果为生产环境会自动向加载根目录下面的配置文件
        if ($env == 'pro') {
            $config_dir = SWOOLE_ROOT_DIR . "/Config/";
            if (!is_dir($config_dir)) {
                echo('Configuration dir not found');
            }
            foreach (glob($config_dir . '*.config.php') as $config_file) {
                $configname = basename($config_file, '.config.php');
                if ($configname != $file_name) {
                    continue;
                }
                @include $config_file;
                if (!empty($$configname)) {
                    $new_config[$configname] = $$configname;
                }
            }
            $app_config_dir = SWOOLE_ROOT_DIR . 'App/' . SWOOLE_APP . "/Config/";
            foreach (glob($app_config_dir . '*.config.php') as $app_config_file) {
                $configname = basename($app_config_file, '.config.php');
                @include $app_config_file;
                if (!empty($$configname)) {
                    $new_config[$configname] = empty($new_config[$configname]) ? array() : $new_config[$configname];
                    $new_config[$configname] = array_merge($new_config[$configname], $$configname);
                }
            }
        }

        $config_dir = SWOOLE_ROOT_DIR . "/Config/{$env}/";
        if (!is_dir($config_dir)) {
            echo("$app_config_dir not found \n");
        }
        foreach (glob($config_dir . '*.config.php') as $config_file) {
            $configname = basename($config_file, '.config.php');
            @include $config_file;
            if (!empty($$configname)) {
                $new_config[$configname] = $$configname;
            }
        }
        $app_config_dir = SWOOLE_ROOT_DIR . 'App/' . SWOOLE_APP . "/Config/{$env}/";
        if (!file_exists($app_config_dir)) {
            echo("$app_config_dir not found, please check your env config \n");
        }
        foreach (glob($app_config_dir . '*.config.php') as $app_config_file) {
            $configname = basename($app_config_file, '.config.php');
            @include $app_config_file;
            if (!empty($$configname)) {
                $new_config[$configname] = empty($new_config[$configname]) ? array() : $new_config[$configname];
                $new_config[$configname] = array_merge($new_config[$configname], $$configname);
                //echo "$configname=>". var_export($this->config[$configname], TRUE)."\n";
            }
        }
        return $new_config[$file_name];
    }

    /**
     * 获取实例
     * @return \Man\Core\Lib\instance
     */
    public static function instance() {
        if (!self::$instances) {
            self::$instances = new self();
        }
        return self::$instances;
    }

    /**
     * 获取单个配置文件
     * @return \Man\Core\Lib\instance
     */
    public static function reload($file_name) {
        return self::get_configs($file_name);
    }

    /**
     * 获取配置
     * @param string $uri
     * @return mixed
     */
    public function get($configname) {
        $return = "";
        if (preg_match_all("/([^\[\]]+)/", $configname, $matches)) {
            if (!empty($matches[1])) {
                foreach ($matches[1] as $value) {
                    $return = isset($return[$value]) ? $return[$value] : (!isset($this->config[$value]) ? false : $this->config[$value]);
                }
            }
        }
        return $return;
    }

}