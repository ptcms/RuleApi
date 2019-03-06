<?php

namespace Kuxin;

/**
 * Class Router
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Router
{

    static $controller = 'index';
    static $action = 'index';

    static $selfConsoleClass = [
        'migrate',
    ];

    /**
     * 解析controller和action
     */
    public static function dispatcher(): void
    {
        //解析s变量
        if (isset($_GET['s'])) {
            $superVar = rtrim($_GET['s'], '/');
            //判断是否需要进行rewrite转换
            if (Config::get('rewrite.power')) {
                $superVar = self::rewrite($superVar);
            }
            if (strpos($superVar, '/')) {
                if (strpos($superVar, '.')) {
                    $param = explode('.', $superVar, 2);
                    if (!Request::isAjax()) {
                        Response::setType($param['1']);
                    }
                    $param = explode('/', $param['0']);
                } else {
                    $param = explode('/', $superVar);
                }
                self::$action     = strtolower(array_pop($param));
                self::$controller = strtolower(implode('\\', $param));
            } else {
                self::$controller = strtolower($superVar);
                self::$action     = 'index';
            }
            unset($_GET['s']);
            unset($_REQUEST['s']);
        }
    }

    /**
     * 正则模式解析
     */
    public static function rewrite($superVar)
    {
        if ($router = Config::get('rewrite.router')) {
            foreach ($router as $rule => $url) {
                if (preg_match('{' . $rule . '}isU', $superVar, $match)) {
                    unset($match['0']);
                    if (strpos($url, '?')) {
                        list($url, $query) = explode('?', $url);
                    }
                    $superVar = rtrim($url, '/');
                    if ($match && !empty($query)) {//组合后面的参数
                        $param = explode('&', $query);
                        if (count($param) == count($match) && $var = array_combine($param, $match)) {
                            $_GET     = array_merge($_GET, $var);
                            $_REQUEST = array_merge($_REQUEST, $var);
                        }
                    }
                    break;
                }
            }
        }
        return $superVar;
    }

    /**
     * 命令行解析
     */
    public static function cli()
    {
        global $argv;
        if (strpos($argv['1'], ':')) {
            $param            = explode(':', $argv['1']);
            self::$action     = array_pop($param);
            self::$controller = implode('\\', $param);
        } else {
            self::$action     = 'run';
            self::$controller = $argv['1'];
        }
        $data = ['argv' => $argv];
        if (!empty($argv['2'])) {
            $param = explode('/', $argv['2']);
            $num   = count($param);
            for ($i = 0; $i < $num; $i += 2) {
                $data[$param[$i]] = $param[$i + 1] ?? "";
            }
        }
        Registry::set('cli_params', $data);
    }
}