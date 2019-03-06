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
     * @param string $name       参数名
     * @param mixed  $defaultVar 默认值
     * @return mixed
     */
    public static function get(string $name = '', $defaultVar = null)
    {
        if ($name == '') {
            return self::getAll();
        }
        $name = strtolower($name);
        if (strpos($name, '.')) {
            //数组模式 找到返回
            $keys = explode('.', $name);
            $data = self::$_config;
            foreach ($keys as $name) {
                if (isset($data[$name])) {
                    $data = $data[$name];
                } else {
                    return $defaultVar;
                }
            }
            return $data;
        } else {
            return self::$_config[$name] ?? $defaultVar;
        }
    }

    /**
     * 获取参数
     *
     * @return mixed
     */
    public static function getAll()
    {
        return self::$_config;
    }


    /**
     * @param string $name
     * @param mixed  $var
     */
    public static function set(string $name, $var): void
    {
        //数组 调用注册方法
        if (is_array($name)) {
            self::register($name);
        } elseif (strpos($name, '.')) {
            $data   = self::$_config;
            $tmp    = &$data;
            $fields = explode('.', $name);
            foreach ($fields as $field) {
                $tmp = &$tmp[$field];
            }
            $tmp           = $var;
            self::$_config = $data;
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

    /**
     * 加载目录配置
     *
     * @param $dir
     */
    public static function LoadDir($dir)
    {
        // todo 缓存所有配置
        $dir   = rtrim($dir, '/');
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $config = Loader::import($dir . '/' . $file);
            if (is_array($config)) {
                self::$_config = Arr::merge(self::$_config, $config);
            }
        }
    }
}