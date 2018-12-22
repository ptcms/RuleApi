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
     * @param string $type
     * @param mixed $default
     * @return array|float|int|mixed|null|string
     */
    public static function get(string $name, string $type = 'int', $default = null)
    {
        return self::param($name, $type, $default, $_GET);
    }

    /**
     * 获取$_POST
     *
     * @param        $name
     * @param string $type
     * @param mixed $default
     * @return array|float|int|mixed|null|string
     */
    public static function post(string $name, string $type = 'int', $default = null)
    {
        return self::param($name, $type, $default, $_POST);
    }

    /**
     * 获取$_REQUEST
     *
     * @param        $name
     * @param string $type
     * @param mixed $default
     * @return array|float|int|mixed|null|string
     */
    public static function request(string $name, string $type = 'int', $default = null)
    {
        return self::param($name, $type, $default, $_REQUEST);
    }

    /**
     * 获取put
     *
     * @param        $name
     * @param string $type
     * @param mixed $default
     * @return array|float|int|mixed|null|string
     */
    public static function put(string $name, string $type = 'int', $default = null)
    {
        parse_str(file_get_contents('php://input'), $input);
        return self::param($name, $type, $default, $input);
    }

    /**
     * 获取$_SERVER
     *
     * @param        $name
     * @param string $type
     * @param mixed $default
     * @return array|float|int|mixed|null|string
     */
    public static function server(string $name, string $type = 'int', $default = null)
    {
        return self::param($name, $type, $default, $_SERVER);
    }

    /**
     * 获取$GLOBALS
     *
     * @param        $name
     * @param string $type
     * @param mixed $default
     * @return array|float|int|mixed|null|string
     */
    public static function globals(string $name, string $type = 'int', $default = null)
    {
        return self::param($name, $type, $default, $GLOBALS);
    }

    /**
     * 获取$_FILES
     *
     * @param        $name
     * @param string $type
     * @param mixed $default
     * @return array|float|int|mixed|null|string
     */
    public static function files(string $name, string $type = 'int', $default = null)
    {
        return self::param($name, $type, $default, $_FILES);
    }

    /**
     * 判断$_REQUEST 是否有某个值
     *
     * @param        $name
     * @param array $type
     * @return bool
     */
    public static function has(string $name, array $type = null): bool
    {
        $type = $type ?: $_REQUEST;
        return isset($type[$name]);
    }

    /**
     * @param $name
     * @param $filter
     * @param mixed $default
     * @param array $param
     * @return array|float|int|mixed|null|string
     */
    public static function param(string $name, $filter = 'int', $default = null, array $param = [])
    {
        if (!isset($param[$name])) {
            return is_callable($default) ? $default($name) : $default;
        } else {
            $defaultVar = null;
            $value      = $param[$name];
        }
        switch ($filter) {
            case 'mixed':
                break;
            case 'int':
                $value = (int)$value;
                break;
            case 'float':
                $value = (float)$value;
                break;
            case 'str':
            case 'string':
                $value = (string)$value;
                break;
            case 'arr':
            case 'array':
                $value = (array)$value;
                break;
            case 'time':
                $value = strtotime($value) ? $value : '0';
                break;
            default:
                if (!Filter::check($value, $filter)) {
                    $value = (null === $defaultVar) ? (is_callable($default) ? $default($name) : $default) : $defaultVar;
                };
        }
        return $value;
    }
}