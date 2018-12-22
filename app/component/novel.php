<?php

namespace App\Component;

use Kuxin\Config;
use Kuxin\DI;
use Kuxin\Helper\Http;
use Kuxin\Helper\Image;

class Novel
{
    
    protected static $cover_ignore = [
        '767d51c0c288a119a0021617e07aec17',
        '885c458c8f2047f10fbe347dffde48fe',
        '4e8e0d052be5d59f05e86cc667ea0cf3',
        '8c8dc7f74a2aa2efe6440a96af1d0fba',
        '69112d56eb663ffbbb6e06749b76c05e',
        '7f7362f276d7acda4eadc18f71981025',
        '3651be6e1b146dbd5810de07aff78f32',
        '39d605f5f65af52b451a9ca4106c9584',
        'd4185bff0ada6042009a1876d3f96a4e',
        '73e8b26cc6daf8167f8edfecb8ad9fbc',
        '7f45805421b479df86d389e893f1b9ff',
        '8a6a4dc2fea4c5deddf6814970a04ed5',
        '7de5137232efaab5c324acd6ce40fe3a',
        '85df644ca2709d9b3b8ce0bf1c1347bb',
        '1940de03c4ee83d5ca064a1d0c6bbb5f',
        'bb2e8e60484b10cb68484cd6f75781f3',
        '4f71b11aa36a2756ed4624c1b3a26f3b',
        '88f75e0c2b987b92952ae734d6409c61',
        '200fea58b89dfebfe00de3cafaa7f3a5',
        '6f6e87de9f61cdc75d836afdb7595c7f',
        '31938f8130e14c528b8f838d2f267ef1',
        '9981a8f73ad447b9138d4615a518fa64',
        '585eb6d76f86abb786516d58fc238be3',
        '622dd0813269c9ea51f1456b208d53c0',
        'b6a6eb372ffa06450a11cd3e5fe73c01',
        '0c94ea41ece4dc4bcc997b25d4404750',
        '7bab2790bd0d180c4faa3d8db7757f64',
        'fb4fcf40d9a0137664ee1781b4f55b7c',
        '2c5448a6de4bbe84aa61c1f4e5c34497',
        'b0239b14cf773658853a3c152d1652a8',//纵横默认封面
    ];
    
    /**
     * 判断图片是否可用
     * 如果不可用 返回false
     *      可用 返回true
     *
     * @param string $imgurl 图片的地址
     * @return bool
     */
    
    public static function checkCover($imgurl)
    {
        
        if (empty($imgurl)) {
            return '';
        }
        $imgurltmp = explode('.', basename(strtolower($imgurl)));
        $imgurltmp = $imgurltmp['0'];
        if (stripos("noimg|nocover|default", $imgurltmp) !== false) {
            return '';
        }
        $coverstr = Http::get($imgurl);
        if (empty($coverstr) or strlen($coverstr) < 100) {
            return '';
        }
        if (stristr($coverstr, '访问的内容不存在') or stristr($coverstr, '<center>') or stristr($coverstr, '<title>') or stristr($coverstr, '404 Not Found')) {
            return '';
        }
        if (stristr($coverstr, '<html') and stristr($coverstr, '<head') and stristr($coverstr, '<title') and stristr($coverstr, '<?php') != false) {
            return '';
        }
        if ($coverstr{0} == '<') {
            return '';
        }
        $imgmd5 = md5($coverstr);
        if (in_array($imgmd5, self::$cover_ignore)) {
            return '';
        }
        return $imgurl;
    }
    
    public static function saveCover($imgurl)
    {
        $coverstr = Http::get($imgurl);
        $imgmd5   = md5($coverstr);
        if (Config::get('collect_cover_save', false)) {
            if (Config::get('collect_cover_api')) {
                return Http::post(Config::get('collect_cover_api'), $coverstr);
            }
            $storage = DI::Storage('cover');
            $file    = "{$imgmd5[0]}{$imgmd5[1]}/{$imgmd5[2]}{$imgmd5[3]}/{$imgmd5[4]}{$imgmd5[5]}/$imgmd5.jpg";
            if (!$storage->exist($file)) {
                $image   = new Image($coverstr);
                $content = $image->save('jpg');
                if (!$content || !$storage->write($file, $content)) {
                    return '';
                }
            }
            $imgurl = $storage->getUrl($file);
        }
        return $imgurl;
    }
    
    /**
     * 获取分类
     *
     * @param     $name
     * @param int $type 男生或者女生
     * @return mixed
     */
    public static function getCategory($name, $type = 1)
    {
        static $data;
        if (!$data) {
            $tmp_boy              = explode("\n", Config::get('collect_category_rule'));
            $tmp_girl             = explode("\n", Config::get('collect_category_girl_rule'));
            $data['default_boy']  = Config::get('collect_category_default');
            $data['default_girl'] = Config::get('collect_category_girl_default');
            foreach ($tmp_boy as $v) {
                $data['rule_boy'][strstr($v, '|', true)] = substr(strstr($v, '='), 1);
            }
            foreach ($tmp_girl as $v) {
                $data['rule_girl'][strstr($v, '|', true)] = substr(strstr($v, '='), 1);
            }
        }
        if ($type == 1) {
            $rule    = $data['rule_boy'];
            $default = $data['default_boy'];
        } else {
            $rule    = $data['rule_girl'];
            $default = $data['default_girl'];
        }
        foreach ($rule as $k => $v) {
            if (preg_match('{' . $v . '}', $name)) {
                return $k;
            }
        }
        return $default;
    }
    
}