<?php
/**
 * @Author: 杰少Pakey
 * @Email : Pakey@qq.com
 * @File  : controller.php
 */

namespace Kuxin;

/**
 * 控制器
 * Class Controller
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Controller
{
    public $disableActions = [];

    public function middleware()
    {
        return null;
    }

    public function init()
    {
    }

    /**
     * ajax返回
     *
     * @param        $data
     * @param string $type
     * @return mixed
     */
    public function ajax(array $data, string $type = 'json'): array
    {
        Response::setType($type);
        return $data;
    }

    /**
     * 跳转
     *
     * @param     $url
     * @param int $code
     */
    public function redirect(string $url, $code = 302): void
    {
        Response::redirect($url, $code);
        exit;
    }
}