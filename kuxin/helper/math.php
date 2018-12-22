<?php

namespace Kuxin\Helper;

/**
 * Class Num
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Math
{
    
    /**
     * 取子id
     *
     * @param $id
     * @return float
     */
    public static function subid($id)
    {
        $id = (int)$id;
        return floor($id / 1000);
    }/**
     * 取子id
     *
     * @param $id
     * @return float
     */
    public static function subIdPlus($id)
    {
        $id = (int)$id;
        return Ceil($id / 1000);
    }
    
    /**
     * 文件大小格式化
     *
     * @param integer $size 初始文件大小，单位为byte
     * @return string 格式化后的文件大小和单位数组，单位为byte、KB、MB、GB、TB
     */
    public static function file_size_format($size = 0, $dec = 2)
    {
        $unit = ["B", "KB", "MB", "GB", "TB", "PB"];
        $pos  = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        $result['size'] = round($size, $dec);
        $result['unit'] = $unit[$pos];
        return $result['size'] . $result['unit'];
    }
}