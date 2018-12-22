<?php

namespace Kuxin\Helper;

/**
 * Class Arr
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Arr
{

    /**
     * 二维数组排序
     *
     * @param        $list
     * @param        $key
     * @param string $order
     * @return array
     */
    public static function msort($list, $key, $order = 'desc')
    {
        $arr = $new = [];
        foreach ($list as $k => $v) {
            $arr[$k] = $v[$key];
        }
        if ($order == 'asc') {
            asort($arr);
        } else {
            arsort($arr);
        }
        foreach ($arr as $k => $v) {
            $new[] = $list[$k];
        }
        return $new;
    }

    /**
     * 数组递归合并
     *
     * @param ...
     * @return bool
     */
    public static function merge()
    {
        $args = func_get_args();
        $rs   = array_shift($args);

        foreach ($args as $arg) {
            if (!is_array($arg)) {
                return false;
            }
            foreach ($arg as $key => $val) {
                $rs[$key] = isset($rs[$key]) ? $rs[$key] : [];
                $rs[$key] = is_array($val) ? self::merge($rs[$key], $val) : $val;
            }
        }
        return $rs;
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     * If the key does not exist in the array or object, the default value will be returned instead.
     *
     * The key may be specified in a dot format to retrieve the value of a sub-array or the property
     * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
     * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
     * or `$array->x` is neither an array nor an object, the default value will be returned.
     * Note that if the array already has an element `x.y.z`, then its value will be returned
     * instead of going through the sub-arrays. So it is better to be done specifying an array of key names
     * like `['x', 'y', 'z']`.
     *
     * Below are some usage examples,
     *
     * ```php
     * // working with array
     * $username = \yii\helpers\ArrayHelper::getValue($_POST, 'username');
     * // working with object
     * $username = \yii\helpers\ArrayHelper::getValue($user, 'username');
     * // working with anonymous function
     * $fullName = \yii\helpers\ArrayHelper::getValue($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * // using dot format to retrieve the property of embedded object
     * $street = \yii\helpers\ArrayHelper::getValue($users, 'address.street');
     * // using an array of keys to retrieve the value
     * $value = \yii\helpers\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param array|object          $array   array or object to extract value from
     * @param string|\Closure|array $key     key name of the array element, an array of keys or property name of the object,
     *                                       or an anonymous function returning the value. The anonymous function signature should be:
     *                                       `function($array, $defaultValue)`.
     *                                       The possibility to pass an array of keys is available since version 2.0.4.
     * @param mixed                 $default the default value to be returned if the specified array key does not exist. Not used when
     *                                       getting value from an object.
     * @return mixed the value of the element if found, default value otherwise
     */
    public static function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key   = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessible beforehand
            return $array->$key;
        } elseif (is_array($array)) {
            return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
        } else {
            return $default;
        }
    }

    public static function group(array $array, string $key)
    {
        $return = [];
        foreach ($array as $value) {
            if (isset($value[$key])) {
                $return[$value[$key]][] = $value;
            }
        }
        return $return;
    }

    public static function rand(array $array, $num = 1)
    {
        if ($num == 1) {
            return $array[array_rand($array, 1)];
        } else {
            $return = [];
            $keys   = array_rand($array, $num);
            foreach ($keys as $key) {
                $return[] = $array[$key];
            }
            return $return;
        }
    }

    public static function unique($array2D,$stkeep=false,$ndformat=true)
    {
        // 判断是否保留一级数组键 (一级数组键可以为非数字)
        if($stkeep) $stArr = array_keys($array2D);
        // 判断是否保留二级数组键 (所有二级数组键必须相同)
        if($ndformat) $ndArr = array_keys(end($array2D));
        //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
        foreach ($array2D as $v){
            $v = join(",",$v);
            $temp[] = $v;
        }
        //去掉重复的字符串,也就是重复的一维数组
        $temp = array_unique($temp);
        //再将拆开的数组重新组装
        foreach ($temp as $k => $v)
        {
            if($stkeep) $k = $stArr[$k];
            if($ndformat)
            {
                $tempArr = explode(",",$v);
                foreach($tempArr as $ndkey => $ndval) $output[$k][$ndArr[$ndkey]] = $ndval;
            }
            else $output[$k] = explode(",",$v);
        }
        return $output;
    }
}