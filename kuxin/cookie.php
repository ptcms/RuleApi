<?php

namespace Kuxin;

/**
 * Class Cookie
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Cookie
{

    /**
     * 默认配置
     *
     * @var array
     */
    protected static $option = [
        'prefix'   => 'kuxin_',
        // cookie 保存时间
        'expire'   => 2592000,
        // cookie 保存路径
        'path'     => '/',
        // cookie 有效域名
        'domain'   => '',
        //  cookie 启用安全传输
        'secure'   => false,
        // httponly设置
        'httponly' => '',
    ];

    public function __construct(array $config = [])
    {
        $this->init($config);
    }

    /**
     * 初始化
     *
     * @param array $config
     */
    public static function init(array $config = []): void
    {
        $config       = [
            'prefix'   => Input::param('prefix', 'string', Config::get('cookie_prefix', 'PTCMS_'), $config),
            // cookie 保存时间
            'expire'   => Input::param('expire', 'int', Config::get('cookie_expire', 2592000), $config),
            // cookie 保存路径
            'path'     => Input::param('path', 'string', Config::get('cookie_path', '/'), $config),
            // cookie 有效域名
            'domain'   => Input::param('domain', 'string', Config::get('cookie_domain', ''), $config),
            //  cookie 启用安全传输
            'secure'   => Input::param('secure', 'string', Config::get('cookie_secure', false), $config),
            // httponly设置
            'httponly' => Input::param('httponly', 'string', Config::get('cookie_httponly', ''), $config),
        ];
        self::$option = array_merge(self::$option, $config);
    }

    /**
     * 获取
     *
     * @param      $name
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $name, $default = null)
    {
        $fullname = self::$option['prefix'] . $name;
        return $_COOKIE[$fullname] ?? ((is_callable($default) ? $default($name) : $default));
    }

    /**
     * 设置cookie
     * @param string $name
     * @param string $value
     * @param array|null $option
     */
    public static function set(string $name, string $value = '',  $option = null): void
    {
        if (!is_null($option)) {
            if (is_numeric($option))
                $option = ['expire' => $option];
            elseif (is_string($option))
                parse_str($option, $option);
            $config = array_merge(self::$option, array_change_key_case($option));
        } else {
            $config = self::$option;
        }
        $name   = self::$option['prefix'] . $name;
        $expire = !empty($config['expire']) ? time() + $config['expire'] : 0;
        setcookie($name, $value, $expire, $config['path'], $config['domain']);
        $_COOKIE[$name] = $value;
    }

    /**
     * 删除单个
     *
     * @param $name
     */
    public static function remove(string $name): void
    {
        $name = self::$option['prefix'] . $name;
        setcookie($name, '', time() - 3600, self::$option['path'], self::$option['domain']);
        // 删除指定cookie
        unset($_COOKIE[$name]);
    }

    /**
     * 清空
     */
    public static function clear(): void
    {
        foreach ($_COOKIE as $key => $val) {
            if (0 === stripos($key, self::$option['prefix'])) {
                setcookie($key, '', time() - 3600, self::$option['path'], self::$option['domain']);
                unset($_COOKIE[$key]);
            }
        }
    }
}