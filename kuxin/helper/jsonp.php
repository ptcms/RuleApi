<?php

namespace Kuxin\Helper;

use Kuxin\Config;
use Kuxin\Input;

/**
 * Class Jsonp
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Jsonp
{
    
    /**
     * @param     $data
     * @param int $format
     * @return string
     */
    public static function encode($data, $format = JSON_UNESCAPED_UNICODE)
    {
        $callback = Input::get(Config::get('jsonp_callback'), 'en', 'ptcms_jsonp');
        return $callback . '(' . json_encode($data, $format) . ');';
    }
    
    /**
     * @param      $data
     * @param bool $assoc
     * @return mixed|null
     */
    public static function decode($data, $assoc = true)
    {
        if (strpos($data, '(')) {
            $data = explode('(', substr($data, 0, -2), 2)[1];
            return json_decode($data, $assoc);
        } else {
            return null;
        }
    }
}