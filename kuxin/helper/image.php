<?php

namespace Kuxin\Helper;

/**
 * Class Image
 *
 * @package Kuxin\Helper
 * @author  Pakey <pakey@qq.com>
 */
class Image
{
    
    public $img;
    public $info;
    /* 水印相关常量定义 */
    //常量，标识左上角水印
    const IMAGE_WATER_NORTHWEST = 1;
    //常量，标识上居中水印
    const IMAGE_WATER_NORTH = 2;
    //常量，标识右上角水印
    const IMAGE_WATER_NORTHEAST = 3;
    //常量，标识左居中水印
    const IMAGE_WATER_WEST = 4;
    //常量，标识居中水印
    const IMAGE_WATER_CENTER = 5;
    //常量，标识右居中水印
    const IMAGE_WATER_EAST = 6;
    //常量，标识左下角水印
    const IMAGE_WATER_SOUTHWEST = 7;
    //常量，标识下居中水印
    const IMAGE_WATER_SOUTH = 8;
    //常量，标识右下角水印
    const IMAGE_WATER_SOUTHEAST = 9;
    
    public function __construct($var)
    {
        if (stripos($var, 'http') === 0) {
            $content = Http::get($var);
        } elseif (strlen($var)<100 && file_exists($var)) {
            $content = file_get_contents($var);
        } else {
            $content = (string)$var;
        }
        $this->info['type'] = $this->gettype($content);
        $this->info['mime'] = 'image/' . $this->info['type'];
        if ('gif' == $this->info['type']) {
            $this->gif = new Image_GIF($content);
            $this->img = imagecreatefromstring($this->gif->image());
        } else {
            $this->img = imagecreatefromstring($content);
        }
        $this->info['width']  = imagesx($this->img);
        $this->info['height'] = imagesy($this->img);
    }
    
    /**
     * 返回图像宽度
     *
     * @return integer 图像宽度
     */
    public function width()
    {
        return $this->info['width'];
    }
    
    /**
     * 返回图像高度
     *
     * @return integer 图像高度
     */
    public function height()
    {
        return $this->info['height'];
    }
    
    /**
     * 返回图像类型
     *
     * @return string 图像类型
     */
    public function type()
    {
        return $this->info['type'];
    }
    
    /**
     * 返回图像MIME类型
     *
     * @return string 图像MIME类型
     */
    public function mime()
    {
        return $this->info['mime'];
    }
    
    /**
     * 返回图像尺寸数组 0 - 图像宽度，1 - 图像高度
     *
     * @return array 图像尺寸
     */
    public function size()
    {
        return [$this->info['width'], $this->info['height']];
    }

    /**
     * 按宽度缩放
     * @param int $width
     * @param bool $force 是否强制 如果小的不扩大
     * @return resource
     */
    public function resizeByWidth(int $width,$force=false)
    {
        if($force === false && $this->info['width']>$width){
            return $this->img;
        }
        $height = ceil($width * $this->info['height'] / $this->info['width']);
        do {
            //创建新图像
            $img = imagecreatetruecolor($width, $height);
            // 调整默认颜色
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);
            
            //裁剪
            imagecopyresampled($img, $this->img, 0, 0, 0, 0, $width, $height, $this->info['width'], $this->info['height']);
            //销毁原图
            imagedestroy($this->img);
            
            //设置新图像
            $this->img = $img;
        } while (!empty($this->gif) && $this->gifNext());
        
        $this->info['width']  = $width;
        $this->info['height'] = $height;
        return $this->img;
    }
    
    public function thumb($width, $height)
    {
        //判断尺寸
        if ($this->info['width'] < $width && $this->info['height'] < $height) {
            //创建图像资源 需要填充的
            $img = imagecreatetruecolor($width, $height);
            imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
            //全都小于指定缩略图尺寸
            if ($width / $this->info['width'] > $height / $this->info['height']) {
                //按高放大
                $this->resize(floor($this->info['width'] * $height / $this->info['height']));
                $x = ceil(($width - $this->info['width']) / 2);
                imagecopyresampled($img, $this->img, $x, 0, 0, 0, $this->info['width'], $height, $this->info['width'], $this->info['height']);
            } else {
                //按宽放大
                $this->resize($width);
                $y = ceil(($height - $this->info['height']) / 2);
                imagecopyresampled($img, $this->img, 0, $y, 0, 0, $width, $this->info['height'], $this->info['width'], $this->info['height']);
            }
            //销毁原图
            imagedestroy($this->img);
            //设置新图像
            $this->img            = $img;
            $this->info['width']  = $width;
            $this->info['height'] = $height;
        } else {
            if ($width / $this->info['width'] > $height / $this->info['height']) {
                //按宽缩小
                $this->resize($width);
                $y = ($this->info['height'] - $height) / 2;
                $this->crop($width, $height, 0, $y);
            } else {
                //按高缩小
                $this->resize(floor($this->info['width'] * $height / $this->info['height']));
                $x = ($this->info['width'] - $width) / 2;
                $this->crop($width, $height, $x, 0);
            }
        }
    }
    
    public function crop($w, $h, $x = 0, $y = 0)
    {
        //设置保存尺寸
        do {
            //创建新图像
            $img = imagecreatetruecolor($w, $h);
            // 调整默认颜色
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);
            //裁剪
            imagecopyresampled($img, $this->img, 0, 0, $x, $y, $w, $h, $w, $h);
            //销毁原图
            imagedestroy($this->img);
            //设置新图像
            $this->img = $img;
        } while (!empty($this->gif) && $this->gifNext());
        
        $this->info['width']  = $w;
        $this->info['height'] = $h;
        return $this->img;
    }
    
    public function water($source, $posotion = Image::IMAGE_WATER_SOUTHEAST, $alpha = 60)
    {
        //资源检测
        if (!is_file($source)) return false;
        //获取水印图像信息
        $info = getimagesize($source);
        if ($info === false || $this->info['width'] < $info['0'] || $this->info['height'] < $info['1']) return false;
        //创建水印图像资源
        $fun   = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);
        //设定水印图像的混色模式
        imagealphablending($water, true);
        switch ($posotion) {
            /* 右下角水印 */
            case Image::IMAGE_WATER_SOUTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;
            
            /* 左下角水印 */
            case Image::IMAGE_WATER_SOUTHWEST:
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;
            
            /* 左上角水印 */
            case Image::IMAGE_WATER_NORTHWEST:
                $x = $y = 0;
                break;
            
            /* 右上角水印 */
            case Image::IMAGE_WATER_NORTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = 0;
                break;
            
            /* 居中水印 */
            case Image::IMAGE_WATER_CENTER:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            
            /* 下居中水印 */
            case Image::IMAGE_WATER_SOUTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;
            
            /* 右居中水印 */
            case Image::IMAGE_WATER_EAST:
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            
            /* 上居中水印 */
            case Image::IMAGE_WATER_NORTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;
            
            /* 左居中水印 */
            case Image::IMAGE_WATER_WEST:
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;
            
            default:
                /* 自定义水印坐标 */
                if (is_array($posotion)) {
                    list($x, $y) = $posotion;
                } else {
                    return false;
                }
        }
        do {
            //添加水印
            $src = imagecreatetruecolor($info[0], $info[1]);
            // 调整默认颜色
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);
            
            imagecopy($src, $this->img, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            imagecopymerge($this->img, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);
            
            //销毁零时图片资源
            imagedestroy($src);
            
        } while (!empty($this->gif) && $this->gifNext());
        
        //销毁水印资源
        imagedestroy($water);
        return true;
    }
    
    /**
     * 图像添加文字
     *
     * @param  string  $text   添加的文字
     * @param  string  $font   字体路径
     * @param  integer $size   字号
     * @param  string  $color  文字颜色
     * @param  integer $locate 文字写入位置
     * @param  string  $margin 文字边距
     * @param  integer $offset 文字相对当前位置的偏移量
     * @param  integer $angle  文字倾斜角度
     * @return mixed
     */
    public function text($text, $font, $size = 20, $color = '#ff0000', $locate = Image::IMAGE_WATER_SOUTHEAST, $margin = '', $offset = 0, $angle = 0)
    {
        //资源检测
        //if (!is_file($font)) return false;
        if ($margin === '') $margin = ceil($size / 2);
        //获取文字信息
        
        $info = imagettfbbox($size, $angle, $font, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);
        
        /* 计算文字初始坐标和尺寸 */
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;
        
        /* 设定文字位置 */
        switch ($locate) {
            /* 右下角文字 */
            case Image::IMAGE_WATER_SOUTHEAST:
                $x += $this->info['width'] - $w - $margin;
                $y += $this->info['height'] - $h - $margin;
                break;
            
            /* 左下角文字 */
            case Image::IMAGE_WATER_SOUTHWEST:
                $x += $margin;
                $y += $this->info['height'] - $h - $margin;
                break;
            
            /* 左上角文字 */
            case Image::IMAGE_WATER_NORTHWEST:
                // 起始坐标即为左上角坐标，无需调整
                $x += $margin;
                $y += $margin;
                break;
            
            /* 右上角文字 */
            case Image::IMAGE_WATER_NORTHEAST:
                $x += $this->info['width'] - $w - $margin;
                $y += $margin;
                break;
            
            /* 居中文字 */
            case Image::IMAGE_WATER_CENTER:
                $x += ($this->info['width'] - $w) / 2;
                $y += ($this->info['height'] - $h) / 2;
                break;
            
            /* 下居中文字 */
            case Image::IMAGE_WATER_SOUTH:
                $x += ($this->info['width'] - $w) / 2;
                $y += $this->info['height'] - $h - $margin;
                break;
            
            /* 右居中文字 */
            case Image::IMAGE_WATER_EAST:
                $x += $this->info['width'] - $w - $margin;
                $y += ($this->info['height'] - $h) / 2;
                break;
            
            /* 上居中文字 */
            case Image::IMAGE_WATER_NORTH:
                $x += ($this->info['width'] - $w) / 2;
                $y += $margin;
                break;
            
            /* 左居中文字 */
            case Image::IMAGE_WATER_WEST:
                $x += $margin;
                $y += ($this->info['height'] - $h) / 2;
                break;
            
            default:
                /* 自定义文字坐标 */
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    $this->crop($this->info['width'], $this->info['height'] + ceil($size * 1.4));
                    $x += $this->info['width'] - $w;
                    $y += $this->info['height'] - $h;
                }
        }
        
        /* 设置偏移量 */
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox     = $oy = $offset;
        }
        
        /* 设置颜色 */
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            return false;
        }
        
        do {
            /* 写入文字 */
            $col = imagecolorallocatealpha($this->img, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->img, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
        } while (!empty($this->gif) && $this->gifNext());
        
        return true;
    }
    
    /**
     * 保存图像
     *
     * @param  string  $type      图像类型
     * @param  boolean $interlace 是否对JPEG类型图像设置隔行扫描
     * @return string
     */
    public function save($type = null, $interlace = true)
    {
        if (empty($this->img)) return '';
        
        //自动获取图像类型
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        //保存图像
        if ('jpeg' == $type || 'jpg' == $type) {
            //JPEG图像设置隔行扫描
            imageinterlace($this->img, $interlace);
            ob_start();
            imagejpeg($this->img);
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        } elseif ('gif' == $type && !empty($this->gif)) {
            return $this->gif->save();
        } else {
            $fun = 'image' . $type;
            ob_start();
            $fun($this->img);
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
    }
    
    protected function gettype($content)
    {
        switch (substr($content, 0, 2)) {
            case chr('137') . 'P':
                return 'png';
                break;
            case 'GI':
                return 'gif';
                break;
            case chr('255') . chr('216'):
                return 'jpg';
                break;
            default:
                return 'jpeg';
                exit('error image type ['.ord(substr($content, 0, 1)).' '.ord(substr($content, 1, 1)).']');
        }
    }
    
    /* 切换到GIF的下一帧并保存当前帧，内部使用 */
    private function gifNext()
    {
        ob_start();
        ob_implicit_flush(0);
        imagegif($this->img);
        $img = ob_get_clean();
        
        $this->gif->image($img);
        $next = $this->gif->nextImage();
        
        if ($next) {
            $this->img = imagecreatefromstring($next);
            return $next;
        } else {
            $this->img = imagecreatefromstring($this->gif->image());
            return false;
        }
    }
    
    /**
     * 析构方法，用于销毁图像资源
     */
    public function __destruct()
    {
        empty($this->img) || imagedestroy($this->img);
        return true;
    }
}

class Image_GIF
{
    
    /**
     * GIF帧列表
     *
     * @var array
     */
    private $frames = [];
    
    /**
     * 每帧等待时间列表
     *
     * @var array
     */
    private $delays = [];
    
    /**
     * 构造方法，用于解码GIF图片
     *
     * @param string $src GIF图片数据
     */
    public function __construct($src = null)
    {
        if (!is_null($src)) {
            
            /* 解码GIF图片 */
            try {
                $de           = new GIFDecoder($src);
                $this->frames = $de->GIFGetFrames();
                $this->delays = $de->GIFGetDelays();
            } catch (\Exception $e) {
                trigger_error("解码GIF图片出错", E_USER_ERROR);
            }
        }
    }
    
    /**
     * 设置或获取当前帧的数据
     *
     * @param  string $stream 二进制数据流
     * @return mixed        获取到的数据
     */
    public function image($stream = null)
    {
        if (is_null($stream)) {
            $current = current($this->frames);
            return false === $current ? reset($this->frames) : $current;
        } else {
            $this->frames[key($this->frames)] = $stream;
            return true;
        }
    }
    
    /**
     * 将当前帧移动到下一帧
     *
     * @return string 当前帧数据
     */
    public function nextImage()
    {
        return next($this->frames);
    }
    
    /**
     * 编码并保存当前GIF图片
     *
     * @return string
     */
    public function save()
    {
        $gif = new GIFEncoder($this->frames, $this->delays, 0, 2, 0, 0, 0, 'bin');
        return $gif->GetAnimation();
    }
    
}


/*
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
::
::	GIFEncoder Version 2.0 by László Zsidi, http://gifs.hu
::
::	This class is a rewritten 'GifMerge.class.php' version.
::
::  Modification:
::   - Simplified and easy code,
::   - Ultra fast encoding,
::   - Built-in errors,
::   - Stable working
::
::
::	Updated at 2007. 02. 13. '00.05.AM'
::
::
::
::  Try on-line GIFBuilder Form demo based on GIFEncoder.
::
::  http://gifs.hu/phpclasses/demos/GifBuilder/
::
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

Class GIFEncoder
{
    
    private $GIF = "GIF89a";        /* GIF header 6 bytes	*/
    private $VER = "GIFEncoder V2.05";    /* Encoder version		*/
    
    private $BUF = [];
    private $LOP = 0;
    private $DIS = 2;
    private $COL = -1;
    private $IMG = -1;
    
    private $ERR = [
        'ERR00' => "Does not supported function for only one image!",
        'ERR01' => "Source is not a GIF image!",
        'ERR02' => "Unintelligible flag ",
        'ERR03' => "Does not make animation from animated GIF source",
    ];
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFEncoder...
    ::
    */
    public function __construct($GIF_src, $GIF_dly, $GIF_lop, $GIF_dis, $GIF_red, $GIF_grn, $GIF_blu, $GIF_mod)
    {
        if (!is_array($GIF_src) && !is_array($GIF_dly)) {
            printf("%s: %s", $this->VER, $this->ERR ['ERR00']);
            exit    (0);
        }
        $this->LOP = ($GIF_lop > -1) ? $GIF_lop : 0;
        $this->DIS = ($GIF_dis > -1) ? (($GIF_dis < 3) ? $GIF_dis : 3) : 2;
        $this->COL = ($GIF_red > -1 && $GIF_grn > -1 && $GIF_blu > -1) ?
            ($GIF_red | ($GIF_grn << 8) | ($GIF_blu << 16)) : -1;
        
        for ($i = 0; $i < count($GIF_src); $i++) {
            if (strtolower($GIF_mod) == "url") {
                $this->BUF [] = fread(fopen($GIF_src [$i], "rb"), filesize($GIF_src [$i]));
            } else if (strtolower($GIF_mod) == "bin") {
                $this->BUF [] = $GIF_src [$i];
            } else {
                printf("%s: %s ( %s )!", $this->VER, $this->ERR ['ERR02'], $GIF_mod);
                exit    (0);
            }
            if (substr($this->BUF [$i], 0, 6) != "GIF87a" && substr($this->BUF [$i], 0, 6) != "GIF89a") {
                printf("%s: %d %s", $this->VER, $i, $this->ERR ['ERR01']);
                exit    (0);
            }
            for ($j = (13 + 3 * (2 << (ord($this->BUF [$i]{10}) & 0x07))), $k = true; $k; $j++) {
                switch ($this->BUF [$i]{$j}) {
                    case "!":
                        if ((substr($this->BUF [$i], ($j + 3), 8)) == "NETSCAPE") {
                            printf("%s: %s ( %s source )!", $this->VER, $this->ERR ['ERR03'], ($i + 1));
                            exit    (0);
                        }
                        break;
                    case ";":
                        $k = false;
                        break;
                }
            }
        }
        $this->GIFAddHeader();
        for ($i = 0; $i < count($this->BUF); $i++) {
            $this->GIFAddFrames($i, $GIF_dly [$i]);
        }
        $this->GIFAddFooter();
        return true;
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFAddHeader...
    ::
    */
    private function GIFAddHeader()
    {
        
        if (ord($this->BUF [0]{10}) & 0x80) {
            $cmap = 3 * (2 << (ord($this->BUF [0]{10}) & 0x07));
            
            $this->GIF .= substr($this->BUF [0], 6, 7);
            $this->GIF .= substr($this->BUF [0], 13, $cmap);
            $this->GIF .= "!\377\13NETSCAPE2.0\3\1" . $this->GIFWord($this->LOP) . "\0";
        }
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFAddFrames...
    ::
    */
    private function GIFAddFrames($i, $d)
    {
        
        $Locals_str = 13 + 3 * (2 << (ord($this->BUF [$i]{10}) & 0x07));
        
        $Locals_end = strlen($this->BUF [$i]) - $Locals_str - 1;
        $Locals_tmp = substr($this->BUF [$i], $Locals_str, $Locals_end);
        
        $Global_len = 2 << (ord($this->BUF [0]{10}) & 0x07);
        $Locals_len = 2 << (ord($this->BUF [$i]{10}) & 0x07);
        
        $Global_rgb = substr($this->BUF [0], 13,
            3 * (2 << (ord($this->BUF [0]{10}) & 0x07)));
        $Locals_rgb = substr($this->BUF [$i], 13,
            3 * (2 << (ord($this->BUF [$i]{10}) & 0x07)));
        
        $Locals_ext = "!\xF9\x04" . chr(($this->DIS << 2) + 0) .
            chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . "\x0\x0";
        
        if ($this->COL > -1 && ord($this->BUF [$i]{10}) & 0x80) {
            for ($j = 0; $j < (2 << (ord($this->BUF [$i]{10}) & 0x07)); $j++) {
                if (
                    ord($Locals_rgb{3 * $j + 0}) == (($this->COL >> 16) & 0xFF) &&
                    ord($Locals_rgb{3 * $j + 1}) == (($this->COL >> 8) & 0xFF) &&
                    ord($Locals_rgb{3 * $j + 2}) == (($this->COL >> 0) & 0xFF)
                ) {
                    $Locals_ext = "!\xF9\x04" . chr(($this->DIS << 2) + 1) .
                        chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . chr($j) . "\x0";
                    break;
                }
            }
        }
        switch ($Locals_tmp{0}) {
            case "!":
                $Locals_img = substr($Locals_tmp, 8, 10);
                $Locals_tmp = substr($Locals_tmp, 18, strlen($Locals_tmp) - 18);
                break;
            case ",":
                $Locals_img = substr($Locals_tmp, 0, 10);
                $Locals_tmp = substr($Locals_tmp, 10, strlen($Locals_tmp) - 10);
                break;
            default:
                $Locals_img = '';
        }
        if (ord($this->BUF [$i]{10}) & 0x80 && $this->IMG > -1) {
            if ($Global_len == $Locals_len) {
                if ($this->GIFBlockCompare($Global_rgb, $Locals_rgb, $Global_len)) {
                    $this->GIF .= ($Locals_ext . $Locals_img . $Locals_tmp);
                } else {
                    $byte          = ord($Locals_img{9});
                    $byte          |= 0x80;
                    $byte          &= 0xF8;
                    $byte          |= (ord($this->BUF [0]{10}) & 0x07);
                    $Locals_img{9} = chr($byte);
                    $this->GIF     .= ($Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp);
                }
            } else {
                $byte          = ord($Locals_img{9});
                $byte          |= 0x80;
                $byte          &= 0xF8;
                $byte          |= (ord($this->BUF [$i]{10}) & 0x07);
                $Locals_img{9} = chr($byte);
                $this->GIF     .= ($Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp);
            }
        } else {
            $this->GIF .= ($Locals_ext . $Locals_img . $Locals_tmp);
        }
        $this->IMG = 1;
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFAddFooter...
    ::
    */
    private function GIFAddFooter()
    {
        $this->GIF .= ";";
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFBlockCompare...
    ::
    */
    private function GIFBlockCompare($GlobalBlock, $LocalBlock, $Len)
    {
        
        for ($i = 0; $i < $Len; $i++) {
            if (
                $GlobalBlock{3 * $i + 0} != $LocalBlock{3 * $i + 0} ||
                $GlobalBlock{3 * $i + 1} != $LocalBlock{3 * $i + 1} ||
                $GlobalBlock{3 * $i + 2} != $LocalBlock{3 * $i + 2}
            ) {
                return (0);
            }
        }
        
        return (1);
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFWord...
    ::
    */
    private function GIFWord($int)
    {
        
        return (chr($int & 0xFF) . chr(($int >> 8) & 0xFF));
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GetAnimation...
    ::
    */
    public function GetAnimation()
    {
        return ($this->GIF);
    }
}


/*
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
::
::	GIFDecoder Version 2.0 by László Zsidi, http://gifs.hu
::
::	Created at 2007. 02. 01. '07.47.AM'
::
::
::
::
::  Try on-line GIFBuilder Form demo based on GIFDecoder.
::
::  http://gifs.hu/phpclasses/demos/GifBuilder/
::
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
*/

Class GIFDecoder
{
    
    private $GIF_buffer = [];
    private $GIF_arrays = [];
    private $GIF_delays = [];
    private $GIF_stream = "";
    private $GIF_string = "";
    private $GIF_bfseek = 0;
    
    private $GIF_screen = [];
    private $GIF_global = [];
    private $GIF_sorted;
    private $GIF_colorS;
    private $GIF_colorC;
    private $GIF_colorF;
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFDecoder ( $GIF_pointer )
    ::
    */
    public function __construct($GIF_pointer)
    {
        $this->GIF_stream = $GIF_pointer;
        
        $this->GIFGetByte(6);    // GIF89a
        $this->GIFGetByte(7);    // Logical Screen Descriptor
        
        $this->GIF_screen = $this->GIF_buffer;
        $this->GIF_colorF = $this->GIF_buffer [4] & 0x80 ? 1 : 0;
        $this->GIF_sorted = $this->GIF_buffer [4] & 0x08 ? 1 : 0;
        $this->GIF_colorC = $this->GIF_buffer [4] & 0x07;
        $this->GIF_colorS = 2 << $this->GIF_colorC;
        
        if ($this->GIF_colorF == 1) {
            $this->GIFGetByte(3 * $this->GIF_colorS);
            $this->GIF_global = $this->GIF_buffer;
        }
        /*
         *
         *  05.06.2007.
         *  Made a little modification
         *
         *
         -	for ( $cycle = 1; $cycle; ) {
         +		if ( GIFDecoder::GIFGetByte ( 1 ) ) {
         -			switch ( $this->GIF_buffer [ 0 ] ) {
         -				case 0x21:
         -					GIFDecoder::GIFReadExtensions ( );
         -					break;
         -				case 0x2C:
         -					GIFDecoder::GIFReadDescriptor ( );
         -					break;
         -				case 0x3B:
         -					$cycle = 0;
         -					break;
         -		  	}
         -		}
         +		else {
         +			$cycle = 0;
         +		}
         -	}
        */
        for ($cycle = 1; $cycle;) {
            if ($this->GIFGetByte(1)) {
                switch ($this->GIF_buffer [0]) {
                    case 0x21:
                        $this->GIFReadExtensions();
                        break;
                    case 0x2C:
                        $this->GIFReadDescriptor();
                        break;
                    case 0x3B:
                        $cycle = 0;
                        break;
                }
            } else {
                $cycle = 0;
            }
        }
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFReadExtension ( )
    ::
    */
    private function GIFReadExtensions()
    {
        $this->GIFGetByte(1);
        for (; ;) {
            $this->GIFGetByte(1);
            if (($u = $this->GIF_buffer [0]) == 0x00) {
                break;
            }
            $this->GIFGetByte($u);
            /*
             * 07.05.2007.
             * Implemented a new line for a new function
             * to determine the originaly delays between
             * frames.
             *
             */
            if ($u == 4) {
                $this->GIF_delays [] = ($this->GIF_buffer [1] | $this->GIF_buffer [2] << 8);
            }
        }
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFReadExtension ( )
    ::
    */
    private function GIFReadDescriptor()
    {
        $this->GIFGetByte(9);
        $GIF_screen = $this->GIF_buffer;
        $GIF_colorF = $this->GIF_buffer [8] & 0x80 ? 1 : 0;
        if ($GIF_colorF) {
            $GIF_code = $this->GIF_buffer [8] & 0x07;
            $GIF_sort = $this->GIF_buffer [8] & 0x20 ? 1 : 0;
        } else {
            $GIF_code = $this->GIF_colorC;
            $GIF_sort = $this->GIF_sorted;
        }
        $GIF_size             = 2 << $GIF_code;
        $this->GIF_screen [4] &= 0x70;
        $this->GIF_screen [4] |= 0x80;
        $this->GIF_screen [4] |= $GIF_code;
        if ($GIF_sort) {
            $this->GIF_screen [4] |= 0x08;
        }
        $this->GIF_string = "GIF87a";
        $this->GIFPutByte($this->GIF_screen);
        if ($GIF_colorF == 1) {
            $this->GIFGetByte(3 * $GIF_size);
            $this->GIFPutByte($this->GIF_buffer);
        } else {
            $this->GIFPutByte($this->GIF_global);
        }
        $this->GIF_string .= chr(0x2C);
        $GIF_screen [8]   &= 0x40;
        $this->GIFPutByte($GIF_screen);
        $this->GIFGetByte(1);
        $this->GIFPutByte($this->GIF_buffer);
        for (; ;) {
            $this->GIFGetByte(1);
            $this->GIFPutByte($this->GIF_buffer);
            if (($u = $this->GIF_buffer [0]) == 0x00) {
                break;
            }
            $this->GIFGetByte($u);
            $this->GIFPutByte($this->GIF_buffer);
        }
        $this->GIF_string .= chr(0x3B);
        /*
           Add frames into $GIF_stream array...
        */
        $this->GIF_arrays [] = $this->GIF_string;
    }
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFGetByte ( $len )
    ::
    */
    
    /*
     *
     *  05.06.2007.
     *  Made a little modification
     *
     *
     -	function GIFGetByte ( $len ) {
     -		$this->GIF_buffer = Array ( );
     -
     -		for ( $i = 0; $i < $len; $i++ ) {
     +			if ( $this->GIF_bfseek > strlen ( $this->GIF_stream ) ) {
     +				return 0;
     +			}
     -			$this->GIF_buffer [ ] = ord ( $this->GIF_stream { $this->GIF_bfseek++ } );
     -		}
     +		return 1;
     -	}
     */
    private function GIFGetByte($len)
    {
        $this->GIF_buffer = [];
        
        for ($i = 0; $i < $len; $i++) {
            if ($this->GIF_bfseek > strlen($this->GIF_stream)) {
                return 0;
            }
            $this->GIF_buffer [] = ord($this->GIF_stream{$this->GIF_bfseek++});
        }
        return 1;
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFPutByte ( $bytes )
    ::
    */
    private function GIFPutByte($bytes)
    {
        for ($i = 0; $i < count($bytes); $i++) {
            $this->GIF_string .= chr($bytes [$i]);
        }
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	PUBLIC FUNCTIONS
    ::
    ::
    ::	GIFGetFrames ( )
    ::
    */
    public function GIFGetFrames()
    {
        return ($this->GIF_arrays);
    }
    
    /*
    :::::::::::::::::::::::::::::::::::::::::::::::::::
    ::
    ::	GIFGetDelays ( )
    ::
    */
    public function GIFGetDelays()
    {
        return ($this->GIF_delays);
    }
}