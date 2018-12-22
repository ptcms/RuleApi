<?php

namespace Kuxin\Helper;

/**
 * Class Serialize
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Serialize
{
    
    /**
     * 序列化
     *
     * @param $data
     * @return string
     */
    public static function encode($data)
    {
        if (function_exists('swoole_serialize')) {
            return swoole_serialize($data);
        } else {
            return serialize($data);
        }
    }
    
    /**
     * 反序列化
     *
     * @param $data
     * @return mixed
     */
    public static function decode($data)
    {
        if (function_exists('swoole_unserialize')) {
            return swoole_unserialize($data);
        } else {
            return unserialize($data);
        }
    }
}