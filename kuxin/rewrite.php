<?php

namespace Kuxin;

class Rewrite
{
    public static $rule = [
        '{page}'       => '(\d+)',
        '{chapterid}'  => '(\d+)',
        '{siteid}'     => '(\d+)',
        '{key}'        => '([a-zA-Z0-9_\-]+)',
        '{searchkey}'  => '([^\?\/]+?)',
        '{novelid}'    => '(\d+)',
        '{author}'     => '([^\?\/]+?)',
        '{authorid}'   => '(\d+)',
        '{pinyin}'     => '([\w\-]+)',
        '{type}'       => '([\w\-]+)',
        '{booklistid}' => '(\d+)',
        '{specialid}'  => '(\d+)',

    ];

    public static function format($param)
    {
        foreach ($param as &$v) {
            $v = '/' . ltrim($v, '/');
        }
        return $param;
    }

    public static function createRouter($param)
    {
        $data = [];
        foreach ($param as $k => $v) {
            $v = ltrim($v, '/');
            if ($v != '') {
                $rules = self::parseRule($v);
                if (strpos($v, '{') === false) {
                    $position = 99;
                } else {
                    $position = substr_count(explode('{', $v, 2)[0], '/') + 1;
                }
                foreach ($rules as $rule) {
                    preg_match_all('/\{([a-z]+)\}/', $rule, $match);
                    foreach ($match['0'] as $p) {
                        $preg = empty(self::$rule[$p]) ? '([a-zA-Z0-9]+)' : self::$rule[$p];
                        $rule = str_replace($p, $preg, $rule);
                    }

                    $rule                                  = rtrim($rule, '/');
                    $data[$position]["^{$rule}(\?(.*))*$"] = str_replace('.', '/', $k) . '?' . implode('&', $match[1]);
                }
            }
        }
        krsort($data);
        $result = [];
        foreach ($data as $value) {
            $result = array_merge($result, $value);
        }
        return $result;
    }

    public static function parseRule($rule)
    {
        preg_match_all('#\[[^\[\]]*?\{\w+\}[^\[\]]*?\]#', $rule, $match);
        $rules = [ $rule ];
        if ($match['0']) {
            if (count($match['0']) == 1) {
                $rules[] = str_replace($match['0']['0'], '', $rule);
            } elseif (count($match['0'] == 2)) {
                $rules[] = str_replace($match['0']['0'], '', $rule);
                $rules[] = str_replace($match['0']['1'], '', $rule);
                $rules[] = str_replace([ $match['0']['0'], $match['0']['1'] ], '', $rule);
            } elseif (count($match['0'] == 3)) {
                $rules[] = str_replace($match['0']['0'], '', $rule);
                $rules[] = str_replace($match['0']['1'], '', $rule);
                $rules[] = str_replace($match['0']['2'], '', $rule);
                $rules[] = str_replace([ $match['0']['0'], $match['0']['1'] ], '', $rule);
                $rules[] = str_replace([ $match['0']['0'], $match['0']['2'] ], '', $rule);
                $rules[] = str_replace([ $match['0']['1'], $match['0']['2'] ], '', $rule);
                $rules[] = str_replace([ $match['0']['0'], $match['0']['1'], $match['0']['2'] ], '', $rule);
            }
            foreach ($rules as &$v) {
                $v = str_replace([ '[', ']' ], '', $v);
            }
        }

        return $rules;
    }

    public static function iisRule($param)
    {
        $data = "[ISAPI_Rewrite]\r\n\r\nCacheClockRate 3600\r\nRepeatLimit 32\r\n";
        foreach ($param as $k => $v) {
            $v = ltrim($v, '/');
            if ($v != '') {
                $param = [];
                preg_match_all('/\{([a-z]+)\}/', $v, $match);
                $n = 1;
                foreach ($match['0'] as $kk => $vv) {
                    $preg = empty(self::$rule[$vv]) ? '([a-zA-Z0-9]+)' : self::$rule[$vv];
                    $v    = str_replace($vv, $preg, $v);
                    ++$n;
                    $param[] = "{$match['1'][$kk]}=$" . $n;
                }
                $data .= "RewriteRule ^(.*)/{$v}(\?(.*))*$ $1/index.php\?s=" . str_replace([
                        '.',
                        '_',
                    ], '/', $k) . '&' . implode('&', $param) . '&$' . ($n + 2) . " [I]\r\n";
            }
        }
        return trim($data);
    }
}