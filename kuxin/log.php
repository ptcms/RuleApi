<?php

namespace Kuxin;

/**
 * Class Log
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Log
{

    /**
     * 日志内容
     *
     * @var array
     */
    protected static $log = [];

    /**
     * 获取日志信息
     *
     * @param string $type 信息类型
     * @return array
     */
    public static function getLog($type = ''): array
    {
        return $type ? (self::$log[$type] ?? []) : self::$log;
    }

    /**
     * 记录日志 默认为pt
     *
     * @param mixed $msg 调试信息
     * @param string $type 信息类型
     * @return void
     */
    public static function record($msg, $type = 'kx'):void
    {
        self::$log[$type][] = "[" . date('Y-m-d H:i:s') . "] " . $msg;;
    }

    /**
     * 清空日志信息
     *
     * @return void
     */
    public static function clear():void
    {
        self::$log = [];
    }

    /**
     * 手动写入指定日志到文件
     *
     * @param string $content
     * @param string $type
     */
    public static function write(string $content, string $type = 'kx', bool $withTime = true):void
    {
        DI::Storage('log')->append($type . '_' . date('Ymd') . '.txt', ($withTime ? ("[" . date('Y-m-d H:i:s') . "] ") : "") . $content . PHP_EOL);
    }

    /**
     * 自动写入指定类型日志
     */
    public static function build():void
    {
        $logBuild = Config::get('log.buildtype', ['pt', 'debug', 'collect', 'collecterror', 'cron']);
        foreach ($logBuild as $type) {
            if (isset(self::$log[$type])) {
                self::write(implode(PHP_EOL, self::$log[$type]), $type, false);
            }
        }
    }
}