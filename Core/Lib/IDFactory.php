<?php

namespace Swoole\Core\Lib;

use \swoole_atomic;
use Swoole\Core\Helper\File;

/**
 * 全局ID自增生成器，保证多进程模式下的全局自增ID的唯一性
 *
 * @author xuelin.zhou
 */
class IDFactory {

    /**
     * 管理原子计数对象集合
     *
     * @var array
     */
    protected $memorys = array();

    /**
     * 自增ID超过了2的32次方（4294967296）时每次就需要用这个值作为基础值再加上atomic获得的值
     *
     * @var array
     */
    private $base_ids = array();

    /**
     * 唯一ID键名集合
     *
     * @var array
     */
    private $keys = array();

    /**
     * 缓存路径
     *
     * @var string
     */
    private $path;

    function __construct($config) {
        //创建缓存目录
        if (empty($config)) {
            return;
        }
        $path = $this->path = !empty($config['savedir']) ? $config['savedir'] : (SWOOLE_APP_DIR . "cache/idfactory/");
        if (!is_dir($path)) {
            File::creat_dir($path);
        }
        $this->load();
        $this->keys = $config['keys'];
        //创建原子计数对象
        foreach ($this->keys as $key) {
            if (!isset($this->base_ids[$key])) {
                $this->base_ids[$key] = 0;
            }
            $this->memorys[$key] = new swoole_atomic();
        }
    }

    /**
     * 获取一个ID，并自增当前key的ID值
     * @param $key 自增键名
     * @return int
     */
    function add($key, $add_value = 1) {
        $swoole_atomic = $this->memorys[$key];
        if (!$swoole_atomic) {
            return false;
        }
        $atomic = $swoole_atomic->add($add_value);
        if ($atomic == 0) {
            $atomic = $swoole_atomic->add($add_value);
        }
        $id = $this->base_ids[$key] + $atomic;
        return $id;
    }

    /**
     * 获取一个ID，并自减当前key的ID值
     * @param $key 自增键名
     * @return int
     */
    function sub($key, $sub_value = 1) {
        $swoole_atomic = $this->memorys[$key];
        if (!$swoole_atomic) {
            return false;
        }
        $atomic = $swoole_atomic->sub($sub_value);
        $id = $atomic;
        if ($id <= 0) {
            return false;
        } else {
            return $id;
        }
    }

    /**
     * 获取当前键名的值
     * @return viod
     */
    public function get($key) {
        return $this->memorys[$key]->get();
    }

    /**
     * 保存所有键值到缓存文件，主要是为来持久化
     * @return viod
     */
    function save() {
        foreach ($this->memorys as $key => $swoole_atomic) {
            $file = $this->path . "{$key}.id";
            File::write_file($file, ($this->base_ids[$key] + $swoole_atomic->get()));
        }
    }

    /**
     * 初始化的时候从缓存文件中加载到内存
     * @return array
     */
    private function load() {
        foreach (glob($this->path . "*.id") as $file) {
            $key = trim(basename($file, ".id"));
            $id = File::read_file($file);
            $this->base_ids[$key] = $id;
        }
    }

    /**
     * 删除一个key
     * @param $key 自增键名
     * @return bool
     */
    function delete($key) {
        unset($this->memorys[$key]);
        unlink($this->path . "{$key}.id");
    }

    /**
     * 清理所有
     * @return viod
     */
    function clean() {
        foreach ($this->memory as $key => $swoole_atomic) {
            unlink($this->path . "{$key}.id");
        }
        unset($this->memory);
    }

    function __get($name) {
        return $this->add($name);
    }

}
