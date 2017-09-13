<?php

use Swoole\Core\Lib\Database;

$relations_single['mysql'] = array(
    'host' => "127.0.0.1",
    'port' => 3307,
    'dbms' => 'mysql',
    'user' => "root",
    'passwd' => "admin888",
    'name' => "relation_data_online_single",
    'charset' => "utf8",
    'setname' => true,
    'persistent' => TRUE, //MySQL长连接
    'errorsqlfile' => SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . "/log/error.sql",
);


$relations_single['pg'] = array(
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
    //'export_sql' => "SELECT t1.* FROM (select %fields% from %gp_tablename% %where%) AS t1 WHERE t1.re_cur_parent_company_id in (SELECT company_id FROM company_data.relation_chain_company)",
    'gpfdist' => 'gpfdist://192.168.9.46:8888/outfile/',
    'filepath' => '/opt/data/mysql_to_gp/outfile/',
    'filetype' => 'csv', //导出文件格式
    'delimiter' => ',', //导出文件分隔符
);
