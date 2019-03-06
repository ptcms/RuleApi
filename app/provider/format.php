<?php

namespace App\Provider;

use Kuxin\Filter;

class Format
{

    //章节格式化
    public static function chapter($content, $url = '')
    {
        $content = htmlspecialchars_decode(htmlspecialchars_decode(urldecode(trim($content))));
        if ($url) {
            $content = str_ireplace(explode('.', parse_url($url, PHP_URL_HOST)), '', $content);
        }
        // 广告过滤 2017-10-11
        $content = preg_replace("{<a.+?a>}isU", "", $content);
        $content = str_replace("温馨提示：按回车[Enter]键返回书目，按←键返回上一页，按→键进入下一页。", "", $content);
        $content = preg_replace("/我是超级[^<]+?nv92[^<]+?/U", "", $content);
        //完全过滤js
        $content = preg_replace('/<script?.*\/script>/', '', $content);

        $content = str_ireplace(['www.', '.com', '.cn', '.cc', '.org', '.me', '.net', 'http', '://'], '', $content);
        $content = str_ireplace(['<?'], '', $content);
        $content = str_ireplace(['<br/>', '<br />', '<br>'], "\n", $content);
        $content = preg_replace('/<\/p>\s*<p>/i', "\n", $content);
        $content = trim(str_ireplace(['<p>', '</p>', "\r"], ["\n", "\n", "\n"], $content));
        $content = str_replace("\r", "\n", $content);
        $content = preg_replace("{\s+　　\s+}", "\n", $content);
        $content = preg_replace("/\n{2,}/", "\n", $content);
        // 去除空格
        $content = trim(str_replace(['　', '&nbsp;', '&#12288;'], ' ', $content));
        $t       = explode("\n", $content);
        foreach ($t as $k => $tt) {
            $tt = trim($tt);
            $tt = preg_replace('/^(　|，|、|。|；|：|？|！)+/', '', $tt);
            $tt = preg_replace('/(　)+$/', '', $tt);
            $tt = trim($tt);
            if (!$tt)
                unset($t[$k]);
            $t[$k] = $tt;
        }
        $content = implode("\n\n", $t);
        // 去除其他html
        $content = strip_tags($content, '<img>');
        // 加换行及空格
        $content = (trim($content));
        return str_replace("\n", "<br/>", $content);
    }

    //整理简介
    public static function intro($str)
    {
        $str = htmlspecialchars_decode(htmlspecialchars_decode($str));
        $str  = str_ireplace(['<br/>', '<br />', '<br>'], "\n", $str);
        $str  = str_replace(["\r", '\n', '\r'], "\n", $str);
        $str  = preg_replace("/\n{2,}/", "\n", $str);
        //完全过滤js
        $str  = preg_replace('/<script?.*\/script>/', '', $str);
        $str  = strip_tags($str);
        $from = ["。", "！", "？", "!", "?", '.', '…'];
        $str  = Filter::safetext($str);
        $str  = trim(str_replace(['　', ' ', '&nbsp;'], '', $str));
        $t    = explode("\n", $str);
        $last = 0;
        foreach ($t as $k => $tt) {
            $tt = trim($tt);
            if ($last > 0 && (in_array($tt, $from) || in_array($tt, ['”', '"', "'", "）", "（"]))) {
                $t[$last] .= $tt;
                unset($t[$k]);
                continue;
            };
            if (!$tt) {
                unset($t[$k]);
                continue;
            }
            if (in_array($tt{0}, [',', '?', ':', ';', '；', '：'])) {
                $tt = preg_replace('/^[,?:;；：]/', '', $tt);
            }
            $t[$k] = $tt;
            $last  = $k;
        }
        return implode("\n", $t);
    }

    /**
     * 整理章节名
     *
     * @param $name
     * @return mixed
     */
    public static function name($name)
    {
        $name = str_replace(['　', '&nbsp;'], ' ', urldecode($name));
        $name = strip_tags(htmlspecialchars_decode(strip_tags($name)));
        //部分小说章节获取不全 去除尾部的省略号
        $name = preg_replace('/\.+$/', '', $name);
        $name = preg_replace('/…+$/', '', $name);
        // 补齐符号
        $arr = [
            ['<', '>'],
            ['[', ']'],
            ['(', ')'],
            ['{', '}'],
            ['〈', '〉'],
            ['《', '》'],
            ['（', '）'],
            ['「', '」'],
            ['『', '』'],
            ['［', '］'],
            ['〖', '〗'],
            ['【', '】'],
            ['｛', '｝'],
        ];
        foreach ($arr as $v) {
            if (strpos($name, $v['0']) !== false && strpos($name, $v['1']) === false) {
                //$name.=$v['1'];
                //break;
                $name = str_replace($v['0'], ' ', $name);
            }
        }
        $name = preg_replace('/(\s|　)+/', ' ', $name);
        $name = preg_replace('/^[\d\s\.]+?第/', '第', $name);
        return $name;
    }

    public static function clearNovelName($name)
    {
        $name = trim(strip_tags($name));
        $name = str_replace(['_塔读文学网', '_甜悦读', '_南京阅明', '_逐浪小说', '_杭州趣阅', '_凤凰网', '_博易创为', '_飞卢', '_品书网', '_蔷薇书院', '_幻文小说网', '_黑岩', '_岳麓', '_红杉万橡', '_二层楼', '_酷匠网', '_酷匠网', '_杭州悦蓝', '_一千零一页', '_书海', '_华夏天空', '_凤鸣轩', '_瑞隆文化', '_中润', '_铁血科技', '_永正'], '', $name);
        $name = str_replace(['(塔读文学网)', '(塔读文学网1)', '(甜悦读)', '(南京阅明)', '(逐浪小说)', '(杭州趣阅)', '(凤凰网)', '(博易创为)', '(飞卢)', '(品书网)', '(蔷薇书院)', '(幻文小说网)', '(黑岩)', '(岳麓)', '(红杉万橡)', '(二层楼)', '(酷匠网)', '(酷匠网)', '(杭州悦蓝)', '(一千零一页)', '(书海)', '(华夏天空)', '(凤鸣轩)', '(瑞隆文化)', '(中润)', '(铁血科技)', '(永正)'], '', $name);
        $name = str_replace(['(书坊)', '《', '》', '(合作)'], '', $name);
        return $name;
    }

    public static function clearAuthorName($name)
    {
        $name = trim(strip_tags($name));
        $name = str_replace(['_塔读文学网', '_甜悦读', '_南京阅明', '_逐浪小说', '_杭州趣阅', '_凤凰网', '_博易创为', '_飞卢', '_品书网', '_蔷薇书院', '_幻文小说网', '_黑岩', '_岳麓', '_红杉万橡', '_二层楼', '_酷匠网', '_酷匠网', '_杭州悦蓝', '_一千零一页', '_书海', '_华夏天空', '_凤鸣轩', '_瑞隆文化', '_中润', '_铁血科技', '_永正'], '', $name);
        $name = str_replace(['(书坊)', '《', '》', '(合作)'], '', $name);
        $name = $name ?: '匿名';
        return $name;
    }

    public static function showIntro($str)
    {
        $str = str_replace('<br />', '<br />　　', nl2br($str));
        $str = str_replace("\n", '', $str);
        return '　　' . $str;
    }

    public static function showChapter($str)
    {
        return '　　' . str_replace("\n", '<br/><br/>　　', $str);
    }
}