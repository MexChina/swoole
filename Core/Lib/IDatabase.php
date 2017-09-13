<?php
namespace Swoole\Core\Lib;
/**
 * Database Driver接口
 * 数据库驱动类的接口
 * @author Tianfeng.Han
 *
 */
interface IDatabase {

    function query($sql);

    function connect();

    function close();

    function lastInsertId();
}
