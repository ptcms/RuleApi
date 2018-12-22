<?php

namespace Kuxin;

use Kuxin\Helper\Arr;

/**
 * 配置
 * Class Config
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Config
{

    /**
     * 缓存存储变量
     *
     * @var array
     */
    protected static $_config = [];

    /**
     * 获取参数
     *
     * @param string $name 参数名
     * @param mixed $defaultVar 默认值
     * @return mixed
     */
    public static function get(string $name = '', $defaultVar = null)
    {
        if ($name == '')
            return self::$_config;
        $name = strtolower($name);
        if (strpos($name, '.')) {
            //数组模式 找到返回
            list($group, $name) = explode('.', $name, 2);
            return self::$_config[$group][$name] ?? $defaultVar;
        } else {
            return self::$_config[$name] ?? $defaultVar;
        }
    }


    /**
     * @param        $name
     * @param  $var
     */
    public static function set(string $name, $var):void
    {
        //数组 调用注册方法
        if (is_array($name)) {
            self::register($name);
        } elseif (strpos($name, '.')) {
            list($group, $name) = explode('.', $name, 2);
            self::$_config[$group][$name] = $var;
        } else {
            self::$_config[$name] = $var;
        }
    }


    /**
     * 注册配置
     *
     * @param $config
     */
    public static function register($config)
    {
        if (is_array($config)) {
            self::$_config = Arr::merge(self::$_config, $config);
        }
    }
}