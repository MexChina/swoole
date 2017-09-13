<?php

namespace Swoole\Core\Helper;

class Appfunc {

    public static function create_tmp_table(array $fields, $table_name, &$savedb) {
        $data_type = array(
            8 => 'bigint',
            253 => 'varchar',
            1 => 'tinyint',
            9 => 'mediumint',
            3 => 'int',
            252 => 'text',
            10 => 'date',
            2 => 'smallint',
            246 => 'decimal',
            4 => 'float',
            5 => 'double',
            16 => 'bit',
        );
        $savedb->select_db("tmp");

        $result = $savedb->query("SHOW TABLES LIKE '$table_name'")->fetchall();
        if (!empty($result)) {
            return "tmp.`$table_name`";
        }
        $table_name = "tmp.`$table_name`";
        $create_sql = "CREATE TABLE $table_name (";
        foreach ($fields as $key => $field) {
            $create_sql .= "`{$field->name}` {$data_type[$field->type]}({$field->length}) DEFAULT '{$field->def}',";
        }
        $create_sql = trim($create_sql, ",");
        $create_sql .= ") ENGINE=MEMORY DEFAULT CHARSET=utf8";
        $result = $savedb->query($create_sql);
        if ($result) {
            return $table_name;
        } else {
            return false;
        }
    }

}
