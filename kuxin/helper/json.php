<?php

namespace Kuxin\Helper;

/**
 * Class Json
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Json
{
    
    /**
     * @param     $data
     * @param int $format
     * @return string
     */
    public static function encode($data, $format = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($data, $format);
    }
    
    /**
     * @param      $data
     * @param bool $assoc
     * @return mixed
     */
    public static function decode($data, $assoc = true)
    {
        return json_decode($data, $assoc);
    }
}