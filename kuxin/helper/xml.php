<?php

namespace Kuxin\Helper;

/**
 * Class Xml
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Xml
{
    
    /**
     * @param        $data
     * @param string $root
     * @param string $attr
     * @param string $encoding
     * @return mixed
     */
    public static function encode($data, $root = 'xml', $attr = '', $encoding = 'utf-8')
    {
        if (is_array($attr)) {
            $_attr = [];
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml  .= "<{$root}{$attr}>";
        $xml  .= self::dataToXml($data);
        $xml  .= "</{$root}>";
        return preg_replace('/[\x00-\x1f]/', '', $xml);
    }
    
    /**
     * 数据XML编码
     *
     * @param mixed  $data 数据
     * @param string $parentkey
     * @return string
     */
    protected static function dataToXml($data, $parentkey = '')
    {
        $xml = '';
        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $key = $parentkey;
            } elseif (substr($key, 0, 10) == '__string__') {
                $xml .= $val;
                continue;
            }
            $key = $key ? $key : 'xmldata';
            $xml .= "<{$key}>";
            if (is_array($val) || is_object($val)) {
                $len = strlen("<{$key}>");
                $con = self::dataToXml($val, $key);
                if (strpos($con, "<{$key}>") === 0) {
                    $con = substr($con, $len, -($len + 1));
                }
                $xml .= $con;
            } elseif (strlen($val) > 150 || preg_match('{[<>&\'|"]+}', $val)) {
                $xml .= '<![CDATA[' . $val . ']]>';
            } else {
                $xml .= $val;
            }
            $xml .= "</{$key}>";
        }
        return $xml;
    }
    
    public static function decode($con)
    {
        if (!$con) {
            return [];
        }
        if ($con{0} == '<') {
            $con = simplexml_load_string($con, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        } else {
            $con = simplexml_load_file($con, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        }
        return json_decode(json_encode($con), true);
    }
    
}