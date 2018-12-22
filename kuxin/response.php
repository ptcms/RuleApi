<?php

namespace Kuxin;

/**
 * Class Response
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Response
{

    /**
     * @var
     */
    protected static $type;

    /**
     * @var array
     */
    protected static $types = ['html', 'json', 'xml', 'jsonp'];

    /**
     * @var bool
     */
    protected static $autoRender = true;

    /**
     * @return string
     */
    public static function getType(): string
    {
        if (self::$type) {
            return self::$type;
        } elseif (Request::isAjax()) {
            return 'json';
        } else {
            return 'html';
        }
    }

    /**
     * @param $type
     * @return bool
     */
    public static function setType(string $type)
    {
        if (in_array($type, self::$types)) {
            return self::$type = $type;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    protected static function getMime(): string
    {
        switch (self::$type) {
            case 'json':
                return 'application/json';
            case 'xml':
                return 'text/xml';
            case 'html':
                return 'text/html';
            default:
                return 'text/html';
        }
    }

    /**
     *
     */
    public static function setHeader(): void
    {
        if (!headers_sent()) {
            //设置系统的输出字符为utf-8
            header("Content-Type: " . self::getMime() . "; charset=utf-8");
            //版权标识
            header("X-Powered-By: PTcms Studio (www.ptcms.com)");
            // 跨域
            if (self::$type == 'json') {
                header('Access-Control-Allow-Origin:*');
                header('Access-Control-Allow-Headers:accept, content-type');
            }
        }
    }

    /**
     * @param string $content
     */
    public static function setBody($content = ''): void
    {
        if (!headers_sent()) {
            self::setHeader();
        }
        echo $content;
    }

    /**
     * 终止相应
     */
    public static function finish()
    {
        exit();
    }

    /**
     *
     */
    public static function disableRender(): void
    {
        self::$autoRender = false;
    }

    /**
     *
     */
    public static function enableRender(): void
    {
        self::$autoRender = true;
    }

    /**
     * @return bool
     */
    public static function isAutoRender(): bool
    {
        return self::$autoRender;
    }

    /**
     * @param     $url
     * @param int $code
     */
    public static function redirect(string $url, $code = 302): void
    {
        if (!headers_sent()) {
            if ($code == 302) {
                header('HTTP/1.1 302 Moved Temporarily');
                header('Status:302 Moved Temporarily'); // 确保FastCGI模式下正常
            } else {
                header('HTTP/1.1 301 Moved Permanently');
                header('Status:301 Moved Permanently');
            }
            header('Location: ' . $url);
            exit;
        } else {
            echo '<script>window.location.href="' . $url . '"</script>';
        }
    }

    /**
     * @return mixed|string
     */
    public static function runInfo()
    {
        if (Config::get('is_gen_html')) {
            return '';
        }
        $tpl    = Config::get('runinfo', 'Power by PTCMS, Processed in {time}(s), Memory usage: {mem}MB.');
        $from[] = '{time}';
        $to[]   = number_format(microtime(true) - Registry::get('_startTime'), 3);
        $from[] = '{mem}';
        $to[]   = number_format((memory_get_usage() - Registry::get('_startUseMems')) / 1024 / 1024, 3);
        if (strpos($tpl, '{net}')) {
            $from[] = '{net}';
            $to[]   = Registry::get('_apinum', 0);
        }
        if (strpos($tpl, '{file}')) {
            $from[] = '{file}';
            $to[]   = count(get_included_files());
        }
        if (strpos($tpl, '{sql}')) {
            $from[] = '{sql}';
            $to[]   = Registry::get('_sqlnum', 0);
        }
        if (strpos($tpl, '{cacheread}')) {
            $from[] = '{cacheread}';
            $to[]   = Registry::get('_cacheRead', 0);
        }
        if (strpos($tpl, '{cachewrite}')) {
            $from[] = '{cachewrite}';
            $to[]   = Registry::get('_cacheWrite', 0);
        }
        if (strpos($tpl, '{cachehit}')) {
            $from[] = '{cachehit}';
            $to[]   = Registry::get('_cacheHit', 0);
        }
        $runtimeinfo = str_replace($from, $to, $tpl);
        return $runtimeinfo;
    }


    /**
     * 下载文件
     *
     * @param        $con
     * @param        $name
     * @param string $type
     */
    public function download(string $con, string $name, $type = 'str')
    {
        $length = ($type == 'file') ? filesize($con) : strlen($con);
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Content-Length: " . $length);
        header('Pragma: cache');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Content-Disposition: attachment; filename="' . urlencode($name) . '"; charset=utf-8'); //下载显示的名字,注意格式
        header("Content-Transfer-Encoding: binary ");
        if ($type == 'file') {
            readfile($con);
        } else {
            echo $con;
        }
    }

    /**
     * 屏幕输出
     *
     * @param $text
     * @param $type
     * @param $line
     * @return mixed
     */
    public static function screen(string $text, string $type, bool $line = true)
    {
        switch ($type) {
            case 'success':
                $color = 'green';
                break;
            case 'error':
                $color = 'red';
                break;
            case 'warning':
                $color = "orangered";
                break;
            case 'info':
                $color = 'darkblue';
                break;
            default:
                $color = $type;
        }
        $line = $line ? '<br/>' . PHP_EOL : '';
        if ($color) {
            echo "<span style='color:{$color}'>{$text}</span>{$line}";
        } else {
            echo "<span>{$text}</span>{$line}";
        }
    }


    /**
     * 终端输出
     *
     * @param $text
     * @param $type
     * @param $line
     * @return mixed
     */
    public static function terminal(string $text, string $type, $line = true)
    {
        $end = chr(27) . "[0m";
        switch (strtolower($type)) {
            case "success":
                $pre = chr(27) . "[32m"; //Green
                break;
            case "error":
                $pre = chr(27) . "[31m"; //Red
                break;
            case "warning":
                $pre = chr(27) . "[33m"; //Yellow
                break;
            case 'info':
                $pre = chr(27) . '[36m'; //蓝色
                break;
            default:
                $pre = "";
                $end = '';
        }
        $line = $line ? PHP_EOL : '';
        if (stripos($text, '<br')) {
            $text = str_ireplace(['<br /><br />', '<br/><br/>', '<br/>', '<br />'], PHP_EOL, $text);
        }
        $text = strip_tags($text);
        $text = preg_replace("#" . PHP_EOL . "+?#", PHP_EOL, $text);
        return $pre . $text . $end . $line;
    }

    public static function error(string $message, string $file, int $line)
    {
        if (Config::get('app.mode') == 'cli') {
            exit($message . '[' . $file . ':' . $line . ']');
        } else {
            if (Config::get('app.debug')) {
                $e['message'] = $message;
                $e['file']    = $file;
                $e['line']    = $line;
                include KX_ROOT . '/kuxin/tpl/error.php';
            } else {
                //                header('HTTP/1.1 404 Not Found');
                //                header("status: 404 Not Found");
                Log::record(sprintf("%s [%s:%s]", $message, $file, $line));
                $file = KX_ROOT . '/404.html';
                if (is_file($file)) {
                    $content = file_get_contents($file);
                    $content = str_replace(['{$sitename}', '{$siteurl}', '{$msg}'], [Config::get('sitename'), Config::get('siteurl'), $message], $content);
                } else {
                    $content = '页面出现错误';
                }
                self::setBody($content);
            }
            self::finish();
        }
    }

    /**
     * @param $arr
     */
    public static function debug($arr)
    {
        echo '<pre>';
        print_r($arr);
        echo '</pre>', PHP_EOL;
    }
}
