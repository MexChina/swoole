<?php

/**
 * 内存服务器优化设置
 * 以下设置需要PHP扩展组件支持，其中 memcache 优先于其他设置，
 * 当 memcache 无法启用时，会自动开启另外的两种优化模式
 */
//内存变量前缀, 可更改,避免同服务器中的程序引用错乱
$memory['prefix'] = 'biserver_';

/* reids设置, 需要PHP扩展组件支持, timeout参数的作用没有查证 */
$memory['redis']['server'] = '';
$memory['redis']['port'] = 6379;
$memory['redis']['pconnect'] = 1;
$memory['redis']['timeout'] = 0;
$memory['redis']['requirepass'] = '';
/**
 * 是否使用 Redis::SERIALIZER_IGBINARY选项,需要igbinary支持,windows下测试时请关闭，否则会出>现错误Reading from client: Connection reset by peer
 * 支持以下选项，默认使用PHP的serializer
 * [重要] 该选项已经取代原来的 $memory['redis']['igbinary'] 选项
 * Redis::SERIALIZER_IGBINARY =2
 * Redis::SERIALIZER_PHP =1
 * Redis::SERIALIZER_NONE =0 //则不使用serialize,即无法保存array
 */
$memory['redis']['serializer'] = 1;

$memory['memcached']['server'] = '127.0.0.1';   // memcache 服务器地址
$memory['memcached']['port'] = 11211;   // memcache 服务器端口
$memory['memcached']['pconnect'] = 1;   // memcache 是否长久连接
$memory['memcached']['timeout'] = 1;   // memcache 服务器连接超时

$memory['memcache']['server'] = '';   // memcache 服务器地址
$memory['memcache']['port'] = 11211;   // memcache 服务器端口
$memory['memcache']['pconnect'] = 1;   // memcache 是否长久连接
$memory['memcache']['timeout'] = 1;   // memcache 服务器连接超时



$memory['apc'] = 1;       // 启动对 apc 的支持
$memory['xcache'] = 1;      // 启动对 xcache 的支持
$memory['eaccelerator'] = 1;     // 启动对 eaccelerator 的支持
$memory['wincache'] = 1;      // 启动对 wincache 的支持