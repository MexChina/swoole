<?php

use Swoole\Core\Lib\Database;

$relations['mysql'] = array(
    'host' => "192.168.1.62",
    'port' => 3307,
    'dbms' => 'mysql',
    'user' => "root",
    'passwd' => "admin888",
    'name' => "relation_data_online",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . "/log/error.sql",
    'dkey' => "resume_id", // 分库分布键
    'dm' => '(__ID%8) + floor(__ID / 40000000) * 8'//分布算法 __ID为替换的主键,php脚本
);


$relations['pg'] = array(
    'host' => "221.228.230.195",
    'port' => 5432,
    'dbms' => 'greeplum',
    'user' => "gpadmin",
    'passwd' => "gpadmin123456",
    'name' => "ifchange_dw",
    'schema' => "bi_data", //模式
    'charset' => "utf8",
    'tables' => ['colleague_relations' => 'colleague_relations', 'schoolmate_relations' => 'schoolmate_relations'], //mysql=>GP 导出表的映射
    /* GP导出时的sql，默认为select * from table；
     * %fields% 导出字段
     * %gp_tablename% 需要导出的GP表名
     * %where% 导出的条件，这里表年代表分布式表的算法，如果没有则不要用这个变量
     */
    'export_sql' => "SELECT t1.* FROM (select %fields% from %gp_tablename% %where%) AS t1 WHERE t1.re_cur_parent_company_id in (SELECT company_id FROM company_data.relation_chain_company)",
    'gpfdist' => 'gpfdist://192.168.9.46:8888/outfile/',
    'filepath' => '/opt/data/mysql_to_gp/outfile/',
    'filetype' => 'csv', //导出文件格式
    'delimiter' => ',', //导出文件分隔符
    'task_worker_num' => 4 //每个进程占用的task的数量,同时也是导出GP时同时执行的数量
);
$relations['redis'] = [
    "hosts" => [
        0 => ["host" => "211.148.28.14", "port" => 46379, "password" => "tgpANxbX6#x5"],
        1 => ["host" => "211.148.28.14", "port" => 46380, "password" => "tgpANxbX6#x5"],
        2 => ["host" => "211.148.28.14", "port" => 46381, "password" => "tgpANxbX6#x5"]],
    "key" => ["colleague_relations" => [0, 4], "schoolmate_relations" => [0, 4]], //由那个字段组成redis的key，顺序为mysql表字段的顺序，从0开始
    "key_pre" => "X:", //数据key的前缀
    "filed_names_pre" => "X-FIELD-LIST:", //保存数据对应的字段名，
    "value_pre" => ["colleague_relations" => "c", "schoolmate_relations" => "s"], //储存数据前缀，用来判断数据来源与那个表，读取的是否就用哪个字段list来解析
    "field" => ["colleague_relations" => [1, "c"], "schoolmate_relations" => [1, "s"]]//由那个字段组成redis的field，如果时字母则当成后缀
];
