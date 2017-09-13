<?php
/**
 * redis 简单类库
 * 支持两种方式：1、单台redis(全功能)   2、集群redis(不支持密码)
 * 单台配置文件格式：
 * $config = [
 *       'host'       => '127.0.0.1',    //主机地址
 *       'port'       => 6379,           //端口
 *       'password'   => '',             //密码
 *       'timeout'    => 0,              //连接超时时间
 *       'expire'     => 0,              //cache生存周期
 *       'persistent' => false,          //是否是长链接
 *       'prefix'     => '',             //前缀
 *       'db'         => 0,              //db
 *   ];
 *
 * 集群配置文件格式：
 * $config = [
 *      'host'  =>  ['127.0.0.1:6379','127.0.0.1:6380','127.0.0.1:6381']    //CLUSTER NODES 命令下所有节点都可以加入，也可以只加入某个slave或者master
 * ]
 * 集群不支持批量写入，支持批量读取
 */

namespace Swoole\Core\Lib\Cache;
use Swoole\Core\Lib\IFace\Cache;
class Redis implements Cache{

    protected $handler = null;
    protected $options = array(
        'expire'=>0,
        'prefix'=>'',
    );

    public function __construct($config){

        if (!extension_loaded('redis')) {
            throw new \Exception('not support: redis');
        }

        $this->options = array_merge($this->options,$config);

        if(is_array($this->options['host'])){
            $this->handler = new \RedisCluster(null,$this->options['host']);
        }else{
            $this->handler = new \Redis;
            $func          = $this->options['persistent'] ? 'pconnect' : 'connect';
            $this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);
            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }
            $this->handler->select($this->options['db']);
        }
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name){
        return is_string($name) ? $this->handler->get($this->options['prefix'] . $name) : $this->handler->MGET($name);
    }

    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param integer   $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null){
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $name = $this->options['prefix'] . $name;
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        } else {
            $result = $this->handler->set($name, $value);
        }
        return $result;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function delete($name){
        $this->handler->delete($this->options['prefix'] . $name);
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear(){
        return $this->handler->flushDB();
    }

    /**
     * 返回句柄对象，可执行其它高级方法
     *
     * @access public
     * @return object
     */
    public function handler(){
        return $this->handler;
    }
}