<?php

namespace Kuxin\Helper;

class Emoji
{

    public static function encode($text)
    {
        $tmpStr = json_encode($text); //暴露出unicode
        $tmpStr = preg_replace("#(\\\ue[0-9a-f]{3})#ie", "addslashes('\\1')", $tmpStr); //将emoji的unicode留下，其他不动
        return json_decode($tmpStr);
    }

    public static function decode($text)
    {
        preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2','UTF-8', pack('H4', '\\1'))", $text);
    }
}