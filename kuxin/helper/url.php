<?php

namespace Kuxin\Helper;

use Kuxin\Config;
use Kuxin\Response;
use Kuxin\Router;

/**
 * Class Url
 * url辅助函数
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Url
{

    /**
     * 获取微信用的当前URL 去掉#后面的内容
     *
     * @return string
     */
    public static function weixin()
    {
        $url = self::current();
        if (strpos($url, '#')) {
            $url = explode('#', $url)['0'];
        }
        return $url;
    }

    /**
     * 获取当前地址
     *
     * @return string
     */
    public static function current()
    {
        if (PHP_SAPI == 'cli') {
            return 'cli';
        }
        if (strpos($_SERVER['REQUEST_URI'], 'http://') === 0) {
            return $_SERVER['REQUEST_URI'];
        }
        $protocol = (!empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off'
            || $_SERVER['SERVER_PORT'] === 443) ? 'https://' : 'http://';

        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST'];
        $uri  = isset($_SERVER['HTTP_X_REAL_URI']) ? $_SERVER['HTTP_X_REAL_URI'] : $_SERVER['REQUEST_URI'];
        return $protocol . $host . $uri;
    }

    /**
     * 生成url
     *
     * @param string $method
     * @param array  $args
     * @param string $type
     * @param array  $ignores
     * @return mixed|string
     */
    public static function build($method = '', $args = [], $type = 'html', $ignores = [])
    {
        if (!empty($_REQUEST['template'])){
            $args['template'] = $_REQUEST['template'];
        }
        static $rules = null, $_method = [], $power = false, $default_data = [], $ignore_params = [], $auto_calc = [];
        if ($rules === null) {
            $rules         = Config::get('rewrite.rules');
            $power         = Config::get('rewrite.power', false);
            $default_data  = Config::get('url.default_data', []);
            $ignore_params = Config::get('url.ignore_param', []);
            $auto_calc     = Config::get('url.auto_calc', []);
        }
        $ignores = array_merge($ignores, $ignore_params);

        foreach ($args as $oarg_k => $oarg_v) {
            if ((isset($default_data[$oarg_k]) && $default_data[$oarg_k] == $oarg_v)) {
                unset($args[$oarg_k]);
            }
        }
        if (empty($_method[$method])) {
            if ($method === '') {
                $_method[$method] = strtolower(str_replace('\\', '.', Router::$controller) . '.' . Router::$action);
            } elseif (substr_count($method, '.') == 0) {
                $_method[$method] = strtolower(str_replace('\\', '.', Router::$controller) . '.' . $method);
            } else {
                $_method[$method] = strtolower($method);
            }
        }
        $method = $_method[$method];
        if ($power && isset($rules[$method])) {
            foreach ($auto_calc as $key => $var) {
                if (isset($args[$key])) {
                    foreach ($var as $item) {
                        $args[$item['name']] = $item['func']($args[$key]);
                    }
                }
            }
            $keys = [];
            $rule = $rules[$method];
            $oargs = $args;
            foreach ($args as $key => &$arg) {
                $keys[] = '{' . $key . '}';
                $arg    = rawurlencode(urldecode($arg));
                if (strpos($rule, '{' . $key . '}')) {
                    unset($oargs[$key]);
                }
            }
            $url = self::clearUrl(str_replace($keys, $args, $rule));
            if (strpos($url, ']')) {
                $url = strtr($url, ['[' => '', ']' => '']);
            }
            if (strpos($url, '{')) {
                foreach ($default_data as $default_k => $default_v) {
                    if (strpos($url, '{' . $default_k . '}')) {
                        $url = str_replace('{' . $default_k . '}', $default_v, $url);
                    }
                }
            }
            if (!empty($oargs) && $ignores) {
                foreach ($oargs as $oarg_k => $oarg_v) {
                    if (in_array($oarg_k, $ignores)) {
                        unset($oargs[$oarg_k]);
                    }
                }
            }
            $url = (substr($url, 0, 1) == '/' ? '' : '/') . $url;
            if (empty($oargs)) {
                return $url;
            } else {
                return $url . (strpos($url, '?') ? '&' : '?') . http_build_query($oargs);
            }
        } else {
            $type = $type ? $type : Response::getType();
            $url  = '/' . strtr($method, '.', '/') . '.' . $type;
            if ($args) {
                $url .= '?' . http_build_query($args);
            }
            return $url;
        }
    }

    /**
     * 清除url中可选参数
     *
     * @param $url
     * @return mixed
     */
    private static function clearUrl($url)
    {
        while (preg_match('#\[[^\[\]]*?\{\w+\}[^\[\]]*?\]#', $url, $match)) {
            $url = str_replace($match['0'], '', $url);
        }
        return $url;
    }
}