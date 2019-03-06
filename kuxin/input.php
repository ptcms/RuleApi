<?php

namespace Kuxin;

/**
 * Class Input
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Input
{

    /**
     * 获取$_GET
     *
     * @param        $name
     * @param        $type
     * @param mixed  $default
     * @return array|float|int|mixed|null|string
     */
    public static function get(string $name = '', $type = '', $default = null)
    {
        return self::param($name, $type, $default, $_GET);
    }

    /**
     * 获取$_POST
     *
     * @param        $name
     * @param        $type
     * @param mixed  $default
     * @return array|float|int|mixed|null|string
     */
    public static function post(string $name = '', $type = '', $default = null)
    {
        return self::param($name, $type, $default, $_POST);
    }

    /**
     * 获取$_REQUEST
     *
     * @param        $name
     * @param        $type
     * @param mixed  $default
     * @return array|float|int|mixed|null|string
     */
    public static function request(string $name = '', $type = '', $default = null)
    {
        return self::param($name, $type, $default, $_REQUEST);
    }

    /**
     * 获取put
     *
     * @param        $name
     * @param        $type
     * @param mixed  $default
     * @return array|float|int|mixed|null|string
     */
    public static function put(string $name = '', $type = '', $default = null)
    {
        parse_str(file_get_contents('php://input'), $input);
        return self::param($name, $type, $default, $input);
    }

    /**
     * 获取$_SERVER
     *
     * @param        $name
     * @param        $type
     * @param mixed  $default
     * @return array|float|int|mixed|null|string
     */
    public static function server(string $name = '', $type = '', $default = null)
    {
        return self::param($name, $type, $default, $_SERVER);
    }

    /**
     * 获取$GLOBALS
     *
     * @param        $name
     * @param        $type
     * @param mixed  $default
     * @return array|float|int|mixed|null|string
     */
    public static function globals(string $name = '', $type = '', $default = null)
    {
        return self::param($name, $type, $default, $GLOBALS);
    }

    /**
     * 获取$_FILES
     *
     * @param        $name
     * @param        $type
     * @param mixed  $default
     * @return array|float|int|mixed|null|string
     */
    public static function files(string $name = '', $type = '', $default = null)
    {
        return self::param($name, $type, $default, $_FILES);
    }

    /**
     * 判断$_REQUEST 是否有某个值
     *
     * @param        $name
     * @param array  $type
     * @return bool
     */
    public static function has(string $name, array $type = null): bool
    {
        $type = $type ?: $_REQUEST;
        return isset($type[$name]);
    }

    /**
     * @param string  $name
     * @param       $filter
     * @param mixed $default
     * @param array $param
     * @return array|float|int|mixed|null|string
     */
    public static function param(string $name, $filter = '', $default = null, array $param = [])
    {
        if ($name == '') {
            return $param;
        }
        if (!isset($param[$name])) {
            return is_callable($default) ? $default($name) : $default;
        } else {
            $value = $param[$name];
            if($filter){
                $value = Filter::check($value, $filter);
            }
        }

        return $value;
    }
}