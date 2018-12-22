<?php

namespace Kuxin;

/**
 * Class Filter
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Filter
{

    /**
     * 默认验证规则
     *
     * @var array
     */
    protected static $validate = [
        //必填
        'require'    => '/.+/',
        //邮箱
        'email'      => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
        //链接
        'url'        => '/^http:\/\/[a-zA-Z0-9]+\.[a-zA-Z0-9]+[\/=\?%\-&_~`@\[\]\':+!]*([^<>\"\"])*$/',
        //货币
        'currency'   => '/^\d+(\.\d+)?$/',
        //数字
        'number'     => '/^\d+$/',
        //邮编
        'zip'        => '/^[0-9]\d{5}$/',
        //电话
        'mobile'        => '/^1[\d]{10}$/',
        //整型
        'integer'    => '/^[-\+]?\d+$/',
        //带小数点
        'double'     => '/^[-\+]?\d+(\.\d+)?$/',
        //英文字母
        'english'    => '/^[a-zA-Z]+$/',
        //中文汉字
        'chinese'    => '/^[\x{4e00}-\x{9fa5}]+$/u',
        //拼音
        'pinyin'     => '/^[a-zA-Z0-9\-\_]+$/',
        //用户名
        'username'   => '/^(?!_)(?!.*?_$)[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{3,15}$/u',
        //英文字符
        'en'         => '/^[a-zA-Z0-9_\s\-\.]+$/',
        //中文字符
        'cn'         => '/^[\w\s\-\x{4e00}-\x{9fa5}]+$/u',
        //安全字符串
        'safestring' => '/^[^\$\?]+$/',
    ];

    /**
     * 校验变量
     *
     * @param $value
     * @param $rule
     * @return bool
     */
    public static function check($value, $rule): bool
    {
        //指定值
        if (is_array($rule)) {
            return in_array($value,$rule);
        } else {
            return self::regex($value, $rule);
        }
    }


    /**
     * 判断是否符合正则
     *
     * @param $value
     * @param $rule
     * @return bool
     */
    public static function regex($value, string $rule): bool
    {
        // 检查是否有内置的正则表达式
        $rule = strtolower($rule);
        if (isset(self::$validate[$rule])) {
            $rule = self::$validate[$rule];
        }
        return preg_match($rule, strval($value)) === 1;
    }

    /**
     * 安全的剔除字符 单行等 用于搜索 链接等地方
     *
     * @param $str
     * @return mixed|string
     */
    public static function safeWord(string $str): string
    {
        if (strlen($str) == 0)
            return '';
        $str       = strip_tags($str);
        $badString = '~!@#$%^&*()+|=\\{}[];\'"/<>?';
        $length    = strlen($badString);
        $pos       = 0;
        while ($pos < $length) {
            $str = str_replace($badString{$pos}, '', $str);
            $pos++;
        }
        return preg_replace('/([\:\r\n\t]+)/', '', $str);
    }

    /**
     * 过滤掉html字符
     *
     * @param string $text
     * @param string $tags 允许的html标签
     * @return mixed|string
     */
    public static function safetext(string $text, string $tags = 'br'): string
    {
        $text = trim($text);
        //完全过滤注释
        $text = preg_replace('/<!--?.*-->/', '', $text);
        //完全过滤动态代码
        $text = preg_replace('/<\?|\?' . '>/', '', $text);
        //完全过滤js
        $text = preg_replace('/<script?.*\/script>/', '', $text);

        $text = str_replace('[', '&#091;', $text);
        $text = str_replace(']', '&#093;', $text);
        $text = str_replace('|', '&#124;', $text);
        //br
        $text = preg_replace('/<br(\s\/)?' . '>/i', '[br]', $text);
        $text = preg_replace('/<p(\s\/)?' . '>/i', '[br]', $text);
        $text = preg_replace('/(\[br\]\s*){10,}/i', '[br]', $text);
        //过滤危险的属性，如：过滤on事件lang js
        while (preg_match('/(<[^><]+)( lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1], $text);
        }
        while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1] . $mat[3], $text);
        }
        //允许的HTML标签
        $text = preg_replace('/<(' . $tags . ')( [^><\[\]]*)>/i', '[\1\2]', $text);
        $text = preg_replace('/<\/(' . $tags . ')>/Ui', '[/\1]', $text);
        //过滤多余html
        $text = preg_replace('/<\/?(html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml|table|td|th|tr|i|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a|b|strong)[^><]*>/i', '', $text);
        //过滤合法的html标签
        while (preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i', $text, $mat)) {
            $text = str_replace($mat[0], str_replace('>', ']', str_replace('<', '[', $mat[0])), $text);
        }
        //转换引号
        while (preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i', $text, $mat)) {
            $text = str_replace($mat[0], $mat[1] . '|' . $mat[3] . '|' . $mat[4], $text);
        }
        //过滤错误的单个引号
        while (preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i', $text, $mat)) {
            $text = str_replace($mat[0], str_replace($mat[1], '', $mat[0]), $text);
        }
        //转换其它所有不合法的 < >
        $text = str_replace('<', '&lt;', $text);
        $text = str_replace('>', '&gt;', $text);
        $text = str_replace('"', '&quot;', $text);
        //反转换
        $text = str_replace('[', '<', $text);
        $text = str_replace(']', '>', $text);
        $text = str_replace('|', '"', $text);
        //过滤多余空格
        $text = str_replace('  ', ' ', $text);
        return $text;
    }
}