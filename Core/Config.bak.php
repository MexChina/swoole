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
        $config_dir = SWOOLE_ROOT_DIR . 'Config/';
        if (!is_dir($config_dir)) {
            echo('Configuration dir not found');
        }
        foreach (glob($config_dir . '*.config.php') as $config_file) {
            $configname = basename($config_file, '.config.php');
            @include_once $config_file;
            if (!empty($$configname)) {
                $this->config[$configname] = $$configname;
            }
        }
        $app_config_dir = SWOOLE_ROOT_DIR . 'App/' . SWOOLE_APP . "/Config/";
        foreach (glob($app_config_dir . '*.config.php') as $app_config_file) {
            $configname = basename($app_config_file, '.config.php');
            @include_once $app_config_file;
            if (!empty($$configname)) {
                $this->config[$configname] = empty($this->config[$configname]) ? array():$this->config[$configname];
                $this->config[$configname] = array_merge($this->config[$configname], $$configname);
            }
        }
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
