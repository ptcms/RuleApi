<?php

namespace Kuxin;

/**
 * Class Session
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Session
{

    /**
     * 启动session
     *
     * @param array $config
     */
    public static function start(array $config = [])
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (!$config) {
            $config = Config::get('session', []);
        }
        if (isset($config['hanlder'])) {
            ini_set("session.save_handler", $config['hanlder']);
            ini_set("session.save_path", $config['path']);
            //ini_set("session.save_path", "tcp://127.0.0.1:11211");
        }
        session_start();
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $name = '', $default = null)
    {
        if ($name == '')
            return $_SESSION;
        //数组模式 找到返回
        if (strpos($name, '.')) {
            //数组模式 找到返回
            $c      = $_SESSION;
            $fields = explode('.', $name);
            foreach ($fields as $field) {
                if (!isset($c[$field]))
                    return (is_callable($default) ? $default($name) : $default);
                $c = $c[$field];
            }
            return $c;
        } elseif (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return (is_callable($default) ? $default($name) : $default);
        }
    }

    /**
     * @param        $key
     * @param mixed $value
     * @return bool
     */
    public static function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    public static function remove(string $key): bool
    {
        if (!isset($_SESSION[$key])) {
            return false;
        }

        unset($_SESSION[$key]);

        return true;
    }

    /**
     * 清空session值
     *
     * @access public
     * @return void
     */
    public static function clear()
    {
        $_SESSION = [];
    }

    /**
     * 注销session
     *
     * @access public
     * @return void
     */
    public static function destory()
    {
        if (session_id()) {
            unset($_SESSION);
            session_destroy();
        }
    }

    /**
     * 当浏览器关闭时,session将停止写入
     *
     * @access public
     * @return void
     */
    public static function close()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}