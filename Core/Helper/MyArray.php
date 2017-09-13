<?php

namespace Swoole\Core\Helper;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Array
 *
 * @author root
 */
class MyArray {

    /**
     * 数组递归合并，需要保留key
     *
     * @return array
     */
    public static function arrayMerge($array1, $array2) {
        $tmparray = $array1;
        foreach ($array2 as $key => $value) {
            if (is_array($value) && is_array($array1[$key])) {
                $tmparray[$key] = self::arrayMerge($array1[$key], $value);
            } else {
                $tmparray[$key] = $value;
            }
        }
        return $tmparray;
    }

    /**
     * 转换数组中的一个元素成为当前数组的key
     *
     * @return array
     */
    public static function changeArrayKey($sArray, $idname = 'work_id') {
        if (!empty($sArray)) {
            $tmp_sArray = array();
            foreach ($sArray as $key => $value) {
                if (!empty($value)) {
                    $tmp_sArray[$value[$idname]] = $value;
                    unset($tmp_sArray[$value[$idname]][$idname]);
                }
            }
            return $tmp_sArray;
        }
        return array();
    }

    /**
     * 多维数组转换成一维数组
     *
     * @return array
     */
    public static function multi2array($array, $skey = "") {
        $return = array();
        foreach ($array as $key => $value) {
            $newkey = $skey ? $skey . "_" . $key : $key;
            if (is_array($value)) {
                $return = array_merge($return, self::multi2array($value, $newkey));
            } else {
                $return[$newkey] = $value;
            }
        }
        return $return;
    }

    /**
     * 一维数组转换为多维数组
     *
     * @return array
     */
    public static function array2multi($array) {
        $return = array();
        foreach ($array as $l_key => $value) {
            $keys = explode("_", $l_key);
            rsort($keys);
            $tmparray = [];
            foreach ($keys as $key) {
                if (empty($tmparray)) {
                    $tmparray[$key] = $value;
                } else {
                    $tmparray[$key] = $tmparray;
                }
            }
            $return = array_merge($return, $tmparray);
        }
        return $return;
    }

    /**
     * 对多维数组按key进行排序
     *
     * @return array
     */
    public static function marray_ksort(&$multi_array) {
        foreach ($multi_array as $key => $value) {
            if (is_array($value)) {
                self::marray_ksort($value);
                $multi_array[$key] = $value;
            }
        }
        ksort($multi_array);
    }

    /**
     * 数组转换成php代码
     *
     * @return array
     */
    public static function arrayeval($array, $level = 0) {
        if (!is_array($array)) {
            return "'" . $array . "'";
        }
        if (is_array($array) && function_exists('var_export')) {
            return var_export($array, true);
        }

        $space = '';
        for ($i = 0; $i <= $level; $i++) {
            $space .= "\t";
        }
        $evaluate = "Array\n$space(\n";
        $comma = $space;
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                $key = is_string($key) ? '\'' . addcslashes($key, '\'\\') . '\'' : $key;
                $val = !is_array($val) && (!preg_match("/^\-?[1-9]\d*$/", $val) || strlen($val) > 12) ? '\'' . addcslashes($val, '\'\\') . '\'' : $val;
                if (is_array($val)) {
                    $evaluate .= "$comma$key => " . arrayeval($val, $level + 1);
                } else {
                    $evaluate .= "$comma$key => $val";
                }
                $comma = ",\n$space";
            }
        }
        $evaluate .= "\n$space)";
        return $evaluate;
    }

    /**
     * 求数组中距离$number 最近的数字
     *
     * @return int
     */
    public static function distance_number($array, $number) {
        $max = max($array);
        $min = min($array);
        $min = abs($number - $min);
        $max = abs($max - $number);
        $max = min($min, $max);
        for ($i = 1; $i <= $max; $i++) {
            $tmp_value = $number + $i;
            if (in_array($tmp_value, $array)) {
                return $tmp_value;
            }
            $tmp_value = $number - $i;
            if (in_array($tmp_value, $array)) {
                return $tmp_value;
            }
        }
    }

}
