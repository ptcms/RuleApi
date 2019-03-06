<?php

namespace Kuxin;

use Kuxin\Helper\Serialize;

/**
 * Class Loader
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Loader
{

    /**
     * @var array
     */
    static $_importFiles = [];

    /**
     * @var array
     */
    static $_class = [];

    /**
     * 加载文件
     *
     * @param string $filename
     * @return mixed
     */
    public static function import(string $filename)
    {
        if (!isset(self::$_importFiles[$filename])) {
            if (is_file($filename)) {
                self::$_importFiles[$filename] = require $filename;
            } else {
                return false;
            }
        }
        return self::$_importFiles[$filename];
    }

    /**
     * 初始化类
     * @param string $class
     * @param array  $args
     * @return mixed
     */
    public static function instance(string $class, array $args = [])
    {
        $key = md5($class . '_' . Serialize::encode($args));
        if (empty(self::$_class[$key])) {
            try {
                self::$_class[$key] = (new \ReflectionClass($class))->newInstanceArgs($args);;
            } catch (\Exception $e) {
                return false;
            }
        }
        return self::$_class[$key];
    }


    /**
     * @param $classname
     */
    public static function autoload($classname): void
    {
        $file = KX_ROOT . '/' . strtr(strtolower($classname), '\\', '/') . '.php';
        Loader::import($file);
    }
}