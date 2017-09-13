<?php

namespace Swoole\App\GetRelations\Logic;

use Swoole\Core\Log;
use Swoole\Core\Helper\System;
use Swoole\Core\Lib\Database;
use Swoole\Core\Lib\RedisClus;

/**
 * 统计异步任务脚本
 *
 * @author xuelin.zhou
 */
class DumpDataTask {

    private $db_configs;
    private $redis;
    private $controller;

    function __construct($params, $controller) {
        $this->controller = $controller;
        $config_name = $params['config_name'];
        $this->db_configs = $this->controller->reload_config($config_name);
        if (!empty($this->db_configs['redis'])) {
            if ($this->redis) {
                $this->redis->close();
            }
            $this->redis = new RedisClus($this->db_configs['redis']['hosts']);
            $conn_rs = $this->redis->connect();
            if (!$conn_rs) {
                Log::writelog("connect redis failed ******");
                $this->redis = null;
            }
        }
        Log::writelog("DumpDataTask init success ......");
    }

    /*
     * 写入数据到mysql、redis
     */

    public function dump_to_mysql($params) {
        $host = $params['Host'];
        $dbname = $params['Db'];
        $prot = $params['Port'];
        $username = $params['Username'];
        $password = $params['Password'];
        $count = $params['count'];
        $sql = $params['sql'];
        $servername = $params['Server_name'];
        $reids_fun = $params['redis_fun'] ?? "";
        $reids_datas = $params['redis_datas'] ?? null;
        if (!$this->db_configs) {
            return false;
        }
        //Log::writelog("start insert into spider db [$servername] $count, memory use " . System::get_used_memory()  . "......");
        if (!empty($host) && !empty($prot) && !empty($username) && !empty($password) && !empty($dbname)) {
            $db_config = array(
                'type' => Database::TYPE_MYSQLi,
                'host' => $host,
                'port' => $prot,
                'user' => $username,
                'passwd' => $password,
                'name' => $dbname,
                'charset' => "utf8",
                'errorsqlfile' => SWOOLE_ROOT_DIR . "App/" . SWOOLE_APP . "/log/" . SWOOLE_ENVIRONMENT . "/error.sql",
            );
            $mysql_conns = $this->controller->db($db_config);
            //Log::write_log(var_export($db_config,true));
            System::exec_time();
            $result = $mysql_conns->query($sql);
            if ($result->result) {
                //Log::writelog("success insert into spider db [$servername]  $count, memory use " . System::get_used_memory() . "......");
            } else {
                Log::writelog("failed insert into spider db [$servername]  $count,mysql error(" . $mysql_conns->errno . ") memory use " . System::get_used_memory() . "******");
            }
            $mysql_conns->close();
        }
        //写入redis
        if (!empty($reids_fun) && !empty($reids_datas)) {
            $redis = new RedisClus($this->db_configs['redis']['hosts']);
            $conn_rs = $redis->connect();
            if ($conn_rs) {
                $rs = $redis->$reids_fun($reids_datas);
                if (is_array($rs)) {
                    Log::writelog("write failed list " . implode(",", $rs) . " ******");
                }
                Log::writelog("write ".count($reids_datas)." to redis success ......");
            } else {
                Log::writelog("connect redis failed ******");
            }
            $redis->close();
        }
        Log::writelog("success insert into db [$servername]  $count  use " . System::exec_time() . "ms ......");
        unset($params);
        unset($mysql_conns);
        return TRUE;
    }

    /*
     * 利用gpfidst导出gp数据到文件
     */

    function gpfdist($params) {
        if (!$this->db_configs) {
            return "empty";
        }
        $sub_table_name = ($params['sub_table_id'] === "null") ? $params['tablename'] : "{$params['tablename']}_{$params['sub_table_id']}";
        $gpconn_str = "host={$this->db_configs['pg']['host']} "
                . "port={$this->db_configs['pg']['port']} "
                . "dbname={$this->db_configs['pg']['name']} "
                . "user={$this->db_configs['pg']['user']} "
                . "password={$this->db_configs['pg']['passwd']}";
        $gp_conn = \pg_connect($gpconn_str);
        $is_dump_from_gp = $params['is_dump_from_gp'] ?? true; //是否需要重GP导出，如果不导出则直接读取上次导出的文件
        if ($gp_conn) {
            //Log::writelog("[$sub_table_name]gp connect success ......");
        } else {
            Log::writelog("[$sub_table_name]gp connect failed ......");
            return "empty";
        }
        $sub_table_name = ($params['sub_table_id'] === "null") ? $params['tablename'] : "{$params['tablename']}_{$params['sub_table_id']}";
        $tablename = $params['tablename'];
        $sub_table_id = $params['sub_table_id'];
        $gpschema = $this->db_configs['pg']['schema'];
        if (!is_numeric($sub_table_id) && $sub_table_id !== "null") {
            Log::writelog("[$sub_table_name]error: sub_table_id is empty ******");
            return "empty";
        }
        if (empty($tablename)) {
            Log::writelog("[$sub_table_name]error: tablename is empty ******");
            return "empty";
        }
        Log::writelog("[$sub_table_name]start do table {$sub_table_name}......");
        $gp_table_name = empty($this->db_configs['pg']['tables'][$tablename]) ? $tablename : $this->db_configs['pg']['tables'][$tablename];   //GP储存表名
        $column_sql = "SELECT attname,typname
     FROM
           pg_attribute
           INNER JOIN pg_class  ON pg_attribute.attrelid = pg_class.oid
           INNER JOIN pg_type   ON pg_attribute.atttypid = pg_type.oid
           LEFT OUTER JOIN pg_attrdef ON pg_attrdef.adrelid = pg_class.oid AND pg_attrdef.adnum = pg_attribute.attnum
           LEFT OUTER JOIN pg_description ON pg_description.objoid = pg_class.oid AND pg_description.objsubid = pg_attribute.attnum
           INNER JOIN pg_namespace on pg_class.relowner=pg_namespace.nspowner
     WHERE
           pg_attribute.attnum > 0
          AND attisdropped <> 't'
          AND pg_namespace.nspname='{$gpschema}'
          AND pg_class.relname= '{$gp_table_name}' 
     ORDER BY pg_attribute.attnum";
        //Log::writelog("column_sql:$column_sql"); 
        $result = pg_query($gp_conn, $column_sql);
        $result_arr = pg_fetch_all($result);
        //echo $column_sql."\n";
        //var_dump($result_arr);
        $extra_table_name = "{$sub_table_name}_externaltable"; //外部表名
        $gpfdist_file = "{$this->db_configs['pg']['gpfdist']}{$sub_table_name}.{$this->db_configs['pg']['filetype']}"; //gpfdist 文件地址
        $file_path = "{$this->db_configs['pg']['filepath']}{$sub_table_name}.{$this->db_configs['pg']['filetype']}";   //导出文件的绝对地址
        if (!$is_dump_from_gp) {//不导出数据
            return $file_path;
        }
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $select_fields = "";
        $drop_sql = "DROP EXTERNAL TABLE IF EXISTS {$gpschema}.{$extra_table_name}";
        $rs = pg_query($gp_conn, $drop_sql);
        //创建gp可写外部表
        $create_sql = "CREATE WRITABLE EXTERNAL TABLE {$gpschema}.{$extra_table_name}(";
        foreach ($result_arr as $field) {
            $create_sql.= "\n" . ("{$field["attname"]} {$field["typname"]},");
            $select_fields .= trim($field["attname"]) . ",";
        }
        $select_fields = trim($select_fields, ",");
        $select_fields = $select_fields ? $select_fields : "*";
        $create_sql = trim($create_sql, ",");
        $create_sql.= ")
                LOCATION ('{$gpfdist_file}')
            FORMAT '{$this->db_configs['pg']['filetype']}' (delimiter '{$this->db_configs['pg']['delimiter']}' null '' escape '\"')
            ENCODING 'UTF8'";
        //Log::writelog("[$sub_table_name]create extra table ......");
        $rs = pg_query($gp_conn, $create_sql);
        if ($rs) {
            Log::writelog("[$sub_table_name]create extra table success......");
        } else {
            return "empty";
        }
        //导出数据
        if ($rs) {
            if ($params['sub_table_id'] !== "null") {
                $algo = str_replace("__ID", $this->db_configs['mysql']['dkey'], $this->db_configs['mysql']['dm']);
                $where = $algo ? "where ({$algo})={$sub_table_id}" : "";
            }
            //导出GP的sql
            $export_sql = $this->db_configs['pg']['export_sql'];
            if (is_array($export_sql)) {
                $export_sql = $export_sql[$tablename];
            }
            if (empty($export_sql)) {
                $dump_out_sql = "insert into {$gpschema}.{$extra_table_name} select {$select_fields} from {$gpschema}.{$gp_table_name}";
            } else {
                $export_sql = str_replace("%fields%", "{$select_fields}", $export_sql);
                $export_sql = str_replace("%gp_tablename%", "{$gpschema}.{$gp_table_name}", $export_sql);
                $export_sql = str_replace("%where%", $where, $export_sql);
                $dump_out_sql = "insert into {$gpschema}.{$extra_table_name} $export_sql";
            }
            //$dump_out_sql = "insert into {$gpschema}.{$extra_table_name} SELECT t1.* FROM (select {$select_fields} from {$gpschema}.{$gp_table_name} where ({$algo})={$sub_table_id}) AS t1 WHERE t1.re_cur_parent_company_id in (SELECT company_id FROM company_data.relation_chain_company)";
            System::exec_time();
            $rs = pg_query($gp_conn, $dump_out_sql);
            Log::writelog("[$tablename] $dump_out_sql , use " . System::exec_time() . "ms ......");
            if (!$rs) {
                Log::writelog("[$tablename] dump data from gp failed ......");
                return "empty";
            }
            Log::writelog("[$sub_table_name] success dump data from gp......");
            $rs = pg_query($gp_conn, $drop_sql);
        }
        pg_close($gp_conn);
        return $file_path;
    }

}
