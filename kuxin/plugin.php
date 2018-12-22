<?php

namespace Kuxin;

/**
 * Class Plugin
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Plugin
{

    /**
     * 调用插件
     *
     * @param      $tag
     * @param mixed $param
     * @return mixed
     */
    public static function call(string $tag, $param = null)
    {
        $methods = Config::get('plugin.' . $tag);
        if ($methods && is_array($methods)) {
            foreach ($methods as $method) {
                $class = Loader::instance($method);
                $param = $class->run($param);
            }
        }
        return $param;
    }
}