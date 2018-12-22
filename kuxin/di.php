<?php

namespace Kuxin;

/**
 * Class DI
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class DI
{

    /**
     * @param string $node
     * @return \Kuxin\Cache
     */
    public static function Cache(string $node = 'common'): \Kuxin\Cache
    {
        $hanlder = Registry::get("cache.{$node}");
        if (!$hanlder) {
            $config = Config::get("cache.{$node}");
            if ($config) {
                $hanlder = Loader::instance('\\Kuxin\\Cache', [$config]);
                if ($hanlder) {
                    Registry::set("cache.{$node}", $hanlder);
                }
            } else {
                trigger_error("缓存节点配置[{$node}]不存在", E_USER_ERROR);
            }
        }
        return $hanlder;
    }

    /**
     * @param string $node
     * @return \Kuxin\Storage
     */
    public static function Storage(string $node = 'common'): \Kuxin\Storage
    {
        $hanlder = Registry::get("storage.{$node}");
        if (!$hanlder) {
            $config = Config::get("storage.{$node}");
            if ($config) {
                $hanlder = Loader::instance('\\Kuxin\\Storage', [$config]);
                if ($hanlder) {
                    Registry::set("storage.{$node}", $hanlder);
                }
            } else {
                trigger_error("Storage节点配置[{$node}]不存在", E_USER_ERROR);
            }
        }
        return $hanlder;
    }

    /**
     * @param string $node
     * @return \Kuxin\Db\Mysql
     */
    public static function DB(string $node = 'common'): \Kuxin\Db\Mysql
    {
        $hanlder = Registry::get("db.{$node}");
        if (!$hanlder) {
            $config = Config::get("database.{$node}");
            if ($config) {
                $hanlder = Loader::instance('\\Kuxin\Db\\' . $config['driver'], [$config['option']]);
                if ($hanlder) {
                    Registry::set("storage.{$node}", $hanlder);
                }
            } else {
                trigger_error("Db节点配置[{$node}]不存在", E_USER_ERROR);
            }
        }
        return $hanlder;
    }
}