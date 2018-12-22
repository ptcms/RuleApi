<?php

namespace Kuxin;

use Kuxin\Helper\Arr;

/**
 * Class View
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class View
{

    // 模板存储变量
    protected static $_vars = [];
    // 模版基地址
    protected static $_path = '';
    // 模版文件名
    protected static $_file = null;
    // layout 开关
    protected static $_layout = false;
    // layout 模板
    protected static $_layoutFile = '';

    /**
     * 开启layout
     */
    public static function enableLayout()
    {
        self::$_layout = true;
    }

    /**
     * 关闭layout
     */
    public static function disableLayout()
    {
        self::$_layout = false;
    }

    /**
     * 配置layoutFile
     * @param string $file
     */
    public static function setLayoutFile(string $file)
    {
        self::$_layoutFile = $file;
    }

    /**
     * @param $file
     */
    public static function setFile(string $file)
    {
        self::$_file = $file;
    }

    /**
     * @param $path
     */
    public static function setPath(string $path)
    {
        self::$_path = $path;
    }

    /**
     * @return string
     */
    public static function getPath()
    {
        if (empty(self::$_path)) {
            self::$_path = KX_ROOT . '/app/view';
        }
        return self::$_path;
    }

    /**
     * 模板变量赋值
     *
     * @access public
     * @param mixed $var
     * @param mixed $value
     */
    public static function set($var, $value = null)
    {
        if (is_array($var)) {
            self::$_vars = Arr::merge(self::$_vars, $var);
        } else {
            self::$_vars[$var] = $value;
        }
    }

    /*
     * 获取模板变量值
     */
    public static function get(string $var = '')
    {
        if ($var == '') {
            return self::$_vars;
        } elseif (isset(self::$_vars[$var])) {
            return self::$_vars[$var];
        } elseif (strpos($var, '.') !== false) {
            $arr = explode('.', $var);
            $tmp = self::$_vars;
            foreach ($arr as $v) {
                if (substr($v, 0, 1) === '$') {
                    $v = self::get($v);
                }
                $tmp = $tmp[$v];
            }
            if (!empty($tmp)) {
                return $tmp;
            }
        }
        return null;
    }

    /**
     * 加载并视图片段文件内容
     *
     * @access public
     * @param string $file 视图片段文件名称
     * @param array $data 附加数据
     * @return string
     */
    public static function make(?string $file = null, ?array $data = [])
    {
        //复制参数
        if ($data) {
            self::set($data);
        }
        //获取模板
        $tplfilepath = self::getTplFilePath($file);
        extract(self::$_vars, EXTR_OVERWRITE);
        ob_start();
        include self::checkCompile($tplfilepath);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * 获得模版位置
     *
     * @param string $tpl 视图模板
     * @return string
     */
    protected static function getTplFilePath(string $tpl = null)
    {
        $tpl = $tpl === null ? self::$_file : $tpl;
        if ($tpl === null) {
            $filepath = self::getPath() . '/' . str_replace('\\', '/', Router::$controller) . '/' . Router::$action . '.html';
        } elseif (substr($tpl, 0, 1) === '/') {
            $filepath = KX_ROOT . $tpl;
        } elseif (substr($tpl, 0, 1) === '@') {
            $filepath = self::getPath() . '/' . substr($tpl, 1) . '.html';
        } else {
            $filepath = dirname(self::getPath() . '/' . str_replace('\\', '/', Router::$controller)) . '/' . $tpl . '.html';
        }
        if (is_file($filepath)) {
            return realpath($filepath);
        } else {
            trigger_error("模版{$tpl}不存在[" . $filepath . ']', E_USER_ERROR);
            return false;
        }
    }

    /**
     * @param $tplfile
     * @return string
     */
    protected static function checkCompile(string $tplfile)
    {
        $compiledName = ltrim(str_replace([KX_ROOT, '/app/views', '/template/'], '/', $tplfile), '/');
        if (!$compiledName) {
            trigger_error('生成的模板缓存文件名为空 [' . $tplfile . ']', E_USER_ERROR);
        }
        $compiledFile = substr(str_replace('/', ',', $compiledName), 0, -5) . '.php';
        $storage      = DI::Storage('template');
        if (Config::get('app.debug') || !$storage->exist($compiledFile) || $storage->mtime($compiledFile) < filemtime($tplfile)) {
            // 获取模版内容
            $content = file_get_contents($tplfile);
            // 解析模版
            $content = self::compile($content);
            //判断是否开启layout
            if (self::$_layout && self::$_layoutFile) {
                $includeFile = self::getTplFilePath(self::$_layoutFile);
                $layout      = self::compile(file_get_contents($includeFile));
                $content     = str_replace('__CONTENT__', $content, $layout);
            }
            $content = '<?php defined(\'KX_ROOT\') || exit(\'Permission denied\');?>' . self::replace($content);
            $storage->write($compiledFile, $content);
        }
        return $storage->getPath($compiledFile);
    }

    /**
     * 模版输出替换
     *
     * @param $content
     * @return string
     */
    protected static function replace(string $content)
    {
        $replace = [
            '__RUNINFO__' => '<?php echo Response::runinfo();?>', // 站点公共目录
        ];
        $content = strtr($content, $replace);
        // 判断是否显示runtime info 信息
        return $content;
    }

    /**
     * 编译解析
     *
     * @param $content
     * @return mixed
     */
    public static function compile(string $content)
    {
        $left  = preg_quote('{', '/');
        $right = preg_quote('}', '/');
        if (strpos($content, '<?xml') !== false) {
            $content = str_replace('<?xml', '<?php echo "<?xml";?>', $content);
        }
        if (!preg_match('/' . $left . '.*?' . $right . '/s', $content))
            return $content;
        // 解析载入
        $content = preg_replace_callback('/' . $left . 'include\s+file\s*\=\s*(\'|\")([^\}]*?)\1\s*' . $right . '/i', ['self', 'parseInlcude'], $content);
        // 解析代码
        $content = preg_replace_callback('/' . $left . '(code|php)' . $right . '(.*?)' . $left . '\/\1' . $right . '/is', ['self', 'parseEncode'], $content);
        // 模板注释
        $content = preg_replace('/' . $left . '\/\*.*?\*\/' . $right . '/s', '', $content);
        $content = preg_replace('/' . $left . '\/\/.*?' . $right . '/', '', $content);
        // 解析变量
        $content = preg_replace_callback('/' . $left . '(\$\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.[\w\-]+)*))((?:\s*\|\s*[\w\:]+(?:\s*=\s*(?:@|"[^"]*"|\'[^\']*\'|#[\w\-]+|\$[\w\-]+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.[\w\-]+)*)|[^\|\:,"\'\s]*?)(?:\s*,\s*(?:@|"[^"]*"|\'[^\']*\'|#[\w\-]+|\$[\w\-]+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.[\w\-]+)*)|[^\|\:,"\'\s]*?))*)?)*)\s*' . $right . '/', ['self', 'parseVariable'], $content);
        // 解析函数
        $content = preg_replace_callback('/' . $left . '(\=|~)\s*(.+?)\s*' . $right . '/', ['self', 'parseFunction'], $content);
        // 解析判断
        $content = preg_replace_callback('/' . $left . '(if|else\s*if)\s+(.+?)\s*' . $right . '/', ['self', 'parseJudgment'], $content);
        $content = preg_replace('/' . $left . 'else\s*' . $right . '/i', '<?php else:?>', $content);
        $content = preg_replace('/' . $left . 'sectionelse\s*' . $right . '/i', '<?php endforeach;else:foreach(array(1) as $__loop):?>', $content);
        $content = preg_replace('/' . $left . '\/if\s*' . $right . '/i', '<?php endif;?>', $content);
        // 解析链接
        $content = preg_replace_callback('/' . $left . 'link\=((?:"[^"]*"|\'[^\']*\'|#\w+|\$\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*)|[^"\'\s]+?)(?:(?:\s+\w+\s*\=\s*(?:"[^"]*"|\'[^\']*\'|#\w+|\$\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*)|[^"\'\s]+?))*?))\s*' . $right . '/i', ['self', 'parseLink'], $content);
        // 解析微件
        $content = preg_replace_callback('/' . $left . 'block((?:\s+\w+\s*\=\s*(?:"[^"]*"|\'[^\']*\'|#\w+|\$\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*)|[^"\'\s]+?))+)\s*' . $right . '/i', ['self', 'parseBlock'], $content);
        // 解析循环
        $content = preg_replace_callback('/' . $left . 'loop\s*=([\'|"]?)(\$?\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*))\1\s*' . $right . '/i', ['self', 'parseLoop'], $content);
        $content = preg_replace_callback('/' . $left . 'loop' . $right . '/i', ['self', 'parseLoop'], $content);
        $content = preg_replace_callback('/' . $left . 'section((?:\s+\w+\s*\=\s*(?:"[^"]*"|\'[^\']*\'|#\w+|\$\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*)|[^"\'\s]+?))+)\s*' . $right . '/i', ['self', 'parseSection'], $content);
        $content = preg_replace('/' . $left . '\/(?:loop|section)\s*' . $right . '/i', '<?php endforeach; endif;?>', $content);
        // 还原代码
        $content = preg_replace_callback('/' . chr(2) . '(.*?)' . chr(3) . '/', ['self', 'parseDecode'], $content);
        // 内容后续处理

        /*if (!APP_DEBUG) {
            $content = preg_replace_callback('/<style[^>]*>([^<]*)<\/style>/isU', array('self', 'parseCss'), $content);
            $content = preg_replace_callback('/<script[^>]*>([^<]+?)<\/script>/isU', array('self', 'parseJs'), $content);
            $content = preg_replace(array("/>\s+</"), array('> <'), $content);
            $content = preg_replace('/\?>\s*<\?php/', '', $content);
            $content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    '), ' ', $content);
            $content = strip_whitespace($content);
        }*/
        // 返回内容
        return $content;
    }

    /**
     * css压缩
     *
     * @param $match
     * @return string
     */
    public static function parseCss(string $match)
    {
        return '<style type = "text/css">' . self::compressCss($match['1']) . '</style>';
    }

    /**
     * js压缩
     *
     * @param $march
     * @return mixed
     */
    public static function parseJs(string $march)
    {
        return str_replace($march['1'], self::compressJS($march['1']), $march['0']);
    }


    /**
     * 解析变量名
     *
     * @param $var
     * @return array|mixed|string
     */
    private static function parseVar($var)
    {
        $var = is_array($var) ? reset($var) : trim($var);
        if (substr($var, 0, 1) !== '$')
            $var = '$' . $var;
        if (preg_match('/^\$\w+(\.[\w\-]+)+$/', $var)) {
            if (substr($var, 0, 4) === '$kx.') {
                $vars = array_pad(explode('.', $var, 3), 3, '');
                switch ($vars[1]) {
                    case 'server':
                        $var = '$_SERVER[\'' . strtoupper($vars[2]) . '\']';
                        break;
                    case 'const':
                        $var = strtoupper($vars[2]);
                        break;
                    case 'config':
                        $var = '\Kuxin\Config::get("' . $vars[2] . '")';
                        break;
                    case 'get':
                        $var = '$_GET[\'' . $vars[2] . '\']';
                        break;
                    case 'post':
                        $var = '$_POST[\'' . $vars[2] . '\']';
                        break;
                    case 'request':
                        $var = '$_REQUEST[\'' . $vars[2] . '\']';
                        break;
                    case 'cookie':
                        $var = 'Cookie("' . $vars[2] . '")';
                        break;
                    default:
                        $var = strtoupper($vars[1]);
                        break;
                }
            } else {
                $var = preg_replace('/\.(\w+)/', '[\'\1\']', $var);
            }
        }
        return $var;
    }

    /**
     * @param $string
     * @param $format
     * @return array
     * $format中值true则按照变量解析 其他为默认值
     */
    private static function parseAttribute(string $string, array $format)
    {
        $attribute = ['_etc' => []];
        preg_match_all('/(?:^|\s+)(\w+)\s*\=\s*(?|(")([^"]*)"|(\')([^\']*)\'|(#)(\w+)|(\$)(\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*))|()([^"\'\s]+?))(?=\s+\w+\s*\=|$)/', $string, $match);
        foreach ($match[0] as $key => $value) {
            $name  = strtolower($match[1][$key]);
            $value = trim($match[3][$key]);
            if (isset($format[$name]) && is_bool($format[$name])) {
                $attribute[$name] = $format[$name] ? self::parseVar($value) : $value;
            } else {
                switch ($match[2][$key]) {
                    case '#':
                        $value = strtoupper($value);
                        break;
                    case '$':
                        $value = self::parseVar($value);
                        break;
                    case '"':
                    case '\'':
                        $value = $match[2][$key] . $value . $match[2][$key];
                        break;
                    default:
                        $value = is_numeric($value) ? $value : var_export($value, true);
                }
                if (isset($format[$name])) {
                    $attribute[$name] = $value;
                } else {
                    $attribute['_etc'][$name] = $value;
                }
            }
        }
        return array_merge($format, $attribute);
    }

    /**
     * 解析变量
     *
     * @param $matches
     * @return string
     */
    private static function parseVariable($matches)
    {
        $variable = self::parseVar($matches[1]);
        if ($matches[2]) {
            preg_match_all('/\s*\|\s*([\w\:]+)(\s*=\s*(?:@|"[^"]*"|\'[^\']*\'|#\w+|\$\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*)|[^\|\:,"\'\s]*?)(?:\s*,\s*(?:@|"[^"]*"|\'[^\']*\'|#\w+|\$\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*)|[^\|\:,"\'\s]*?))*)?(?=\||$)/', $matches[2], $match);
            foreach ($match[0] as $key => $value) {
                $function = $match[1][$key];
                if (strtolower($function) == 'parsetpl') {
                    return "<?php include \\Kuxin\\View::parseTpl($variable);?>";
                } elseif (in_array($function, ['date', 'default'])) {
                    $function = "\\Kuxin\\View::{$function}";
                }
                $param = [$variable];
                preg_match_all('/(?:=|,)\s*(?|(@)|(")([^"]*)"|(\')([^\']*)\'|(#)(\w+)|(\$)(\w+(?:(?:\[(?:[^\[\]]+|(?R))*\])*|(?:\.\w+)*))|()([^\|\:,"\'\s]*?))(?=,|$)/', $match[2][$key], $mat);
                if (array_search('@', $mat[1]) !== false)
                    $param = [];
                foreach ($mat[0] as $k => $v) {
                    switch ($mat[1][$k]) {
                        case '@':
                            $param[] = $variable;
                            break;
                        case '#':
                            $param[] = strtoupper($mat[2][$k]);
                            break;
                        case '$':
                            $param[] = self::parseVar($mat[2][$k]);
                            break;
                        case '"':
                        case '\'':
                            $param[] = $mat[1][$k] . $mat[2][$k] . $mat[1][$k];
                            break;
                        default:
                            $param[] = is_numeric($mat[2][$k]) ? $mat[2][$k] : var_export($mat[2][$k], true);
                    }
                }
                $variable = $function . '(' . implode(',', $param) . ')';
            }
        }
        return "<?php echo $variable;?>";
    }


    /**
     *  解析载入
     *
     * @param $matches
     * @return mixed|string
     */
    private static function parseInlcude($matches)
    {
        //20141215 防止写空导致调用死循环
        if ($matches['2']) {
            $includeFile = self::getTplFilePath($matches['2']);
            $truereturn  = realpath($includeFile);
            if ($truereturn) {
                $content = file_get_contents($truereturn);
                return self::compile($content);
            }
            trigger_error("include参数有误，得不到设置的模版，参数[{$matches['2']}]，解析模版路径[{$includeFile}]", E_USER_ERROR);
        }
        return '';
    }

    /**
     * 解析函数
     *
     * @param $matches
     * @return string
     */
    private static function parseFunction($matches)
    {
        $operate    = $matches[1] === '=' ? 'echo' : '';
        $expression = preg_replace_callback('/\$\w+(?:\.\w+)+/', ['self', 'parseVar'], $matches[2]);
        return "<?php $operate $expression;?>";
    }

    /**
     * 解析判断
     *
     * @param $matches
     * @return string
     */
    private static function parseJudgment($matches)
    {
        $judge     = strtolower($matches[1]) === 'if' ? 'if' : 'elseif';
        $condition = preg_replace_callback('/\$\w+(?:\.\w+)+/', ['self', 'parseVar'], $matches[2]);
        return "<?php $judge($condition):?>";
    }

    /**
     * @param $matches
     * @return string
     */
    private static function parseLink($matches)
    {
        $attribute = self::parseAttribute('_type_=' . $matches[1], ['_type_' => false, 'responsetype' => '""']);
        if (!is_string($attribute['_type_']))
            return $matches[0];
        $var = [];
        foreach ($attribute['_etc'] as $key => $value) {
            $var[] = "'$key'=>$value";
        }
        return "<?php echo \\Kuxin\\Helper\\Url::build(\"{$attribute['_type_']}\",[" . implode(',', $var) . "],{$attribute['responsetype']});?>";
    }

    /**
     * @param $matches
     * @return string
     */
    private static function parseBlock($matches)
    {
        $attribute = self::parseAttribute($matches[1], ['method' => false, 'name' => false]);
        $var       = [];
        foreach ($attribute['_etc'] as $key => $value) {
            $var[] = "'$key'=>$value";
        }
        if (empty($attribute['name']) || $attribute['name'] === false) {
            return "<?php echo \\Kuxin\\Block::show('{$attribute['method']}', [" . implode(',', $var) . "]);?>";
        } else {
            $name = '$' . $attribute['name'];
            return "<?php $name=\\Kuxin\\Block::show('{$attribute['method']}', [" . implode(',', $var) . "]);?>";
        }
    }

    /**
     * 解析循环
     *
     * @param $matches
     * @return string
     */
    private static function parseLoop($matches)
    {
        $loop = empty($matches[2]) ? '$list' : (self::parseVar($matches[2]));
        return "<?php if(is_array($loop)): foreach($loop as \$key =>\$loop):?>";
    }

    /**
     * @param $matches
     * @return string
     */
    private static function parseSection($matches)
    {
        $attribute = self::parseAttribute($matches[1], ['loop' => true, 'name' => true, 'item' => true, 'cols' => '1', 'skip' => '0', 'limit' => 'null']);
        if (!is_string($attribute['loop']))
            return $matches[0];
        $name = is_string($attribute['name']) ? $attribute['name'] : '$i';
        $list = is_string($attribute['item']) ? $attribute['item'] : '$loop';
        return "<?php if(is_array({$attribute['loop']}) && (array()!={$attribute['loop']})): $name=array(); {$name}['loop']=array_slice({$attribute['loop']},{$attribute['skip']},{$attribute['limit']},true); {$name}['total']=count({$attribute['loop']}); {$name}['count']=count({$name}['loop']); {$name}['cols']={$attribute['cols']}; {$name}['add']={$name}['count']%{$attribute['cols']}?{$attribute['cols']}-{$name}['count']%{$attribute['cols']}:0; {$name}['order']=0; {$name}['row']=1;{$name}['col']=0;foreach(array_pad({$name}['loop'],{$name}['add'],array()) as {$name}['index']=>{$name}['list']): $list={$name}['list']; {$name}['order']++; {$name}['col']++; if({$name}['col']=={$attribute['cols']}): {$name}['col']=0; {$name}['row']++; endif; {$name}['first']={$name}['order']==1; {$name}['last']={$name}['order']=={$name}['count']; {$name}['extra']={$name}['order']>{$name}['count'];?>";
    }

    /**
     *  保护代码
     *
     * @param $matches
     * @return string
     */
    private static function parseEncode($matches)
    {
        return chr(2) . base64_encode(strtolower($matches[1]) === 'php' ? "<?php {$matches[2]};?>" : trim($matches[2])) . chr(3);
    }

    /**
     * 还原代码
     *
     * @param $matches
     * @return bool|string
     */
    private static function parseDecode($matches)
    {
        return base64_decode($matches[1]);
    }

    /**
     * @param $content
     * @return string
     */
    public static function compressJS($content)
    {
        $lines = explode("\n", $content);
        foreach ($lines as &$line) {
            $line = trim($line) . "\n";
        }
        return implode('', $lines);
    }

    /**
     * @param $content
     * @return mixed
     */
    public static function compressCss($content)
    {

        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content); //删除注释
        $content = preg_replace('![ ]{2,}!', ' ', $content); //删除注释
        $content = str_replace(["\r\n", "\r", "\n", "\t"], '', $content); //删除空白
        return $content;
    }

    /**
     * 默认值函数
     *
     * @return string
     */
    public static function default()
    {
        $args  = func_get_args();
        $value = array_shift($args);
        if (!is_numeric($value)) {
            return $value;
        } elseif (isset($args[$value])) {
            return $args[$value];
        } else {
            return '';
        }
    }


    /**
     * 时间函数优化
     *
     * @param $time
     * @param $format
     * @return mixed
     */
    public static function date($time, $format)
    {
        if ($time == '0')
            return '';
        return date($format, $time);
    }

    /**
     * @param string $content
     * @return string
     */
    public static function parseTpl($content)
    {
        if ($content == '')
            return '';
        $storage   = DI::Storage('template');
        $cachefile = 'parsetpl/' . md5($content . '_parseTpl') . '.php';
        if ($storage->exist($cachefile)) {
            $content = self::compile($content);
            $storage->write($cachefile, $content);
        }
        return $cachefile;
    }
}