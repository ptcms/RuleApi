<?php

namespace Kuxin;

/**
 * 命令行
 * Class Console
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Console
{

    /**
     * 命令行参数
     * @var array
     */
    protected $params = [];

    /**
     * Console constructor.
     */
    public function __construct()
    {
        if((int)ini_get('memory_limit')<1024){
            ini_set('memory_limit','1024M');
        }
        set_time_limit(0);
        $this->params = Registry::get('cli_params', []);
    }

    /**
     * 初始化
     */
    public function init()
    {

    }

    /**
     * 终端输出
     *
     * @param $text
     * @param $status
     * @param $line
     * @return mixed
     */
    public function show(string $text, string $status = 'text', bool $line = true): void
    {
        printf(Response::terminal($text, $status, $line));
    }

    /**
     * 终端输出
     *
     * @param string $text
     * @param bool $line
     * @return mixed
     */
    public function info(string $text, bool $line = true): void
    {
        printf(Response::terminal($text, 'info', $line));
    }

    /**
     * @param string $text
     * @param bool $line
     * @return mixed
     */
    public function success(string $text, bool $line = true): void
    {
        printf(Response::terminal($text, 'success', $line));
    }

    /**
     * @param string $text
     * @param bool $line
     * @return mixed
     */
    public function warning(string $text, bool $line = true): void
    {
        printf(Response::terminal($text, 'success', $line));
    }

    /**
     * @param string $text
     * @param bool $line
     * @return mixed
     */
    public function error(string $text, bool $line = true): void
    {
        printf(Response::terminal($text, 'error', $line));
    }


    /**
     * 获取参数
     * @param string $key
     * @param string $type
     * @param null   $default
     * @return array|float|int|mixed|null|string
     */
    public function param(string $key, string $type = 'int', $default = null)
    {
        return Input::param($key, $type, $default, $this->params);
    }

    /**
     * 终端给提示获取用户数据
     * @param string $text
     * @param string $status
     * @return string
     */
    public function prompt(string $text = '请输入', string $status = 'text')
    {
        //提示输入
        fwrite(STDOUT, Response::terminal($text . ":", $status, false));
        //获取用户输入数据
        $result = trim(fgets(STDIN));
        return $result;
    }
}