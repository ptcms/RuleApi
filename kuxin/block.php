<?php

namespace Kuxin;

use Kuxin\Helper\Json;

/**
 * Class Block
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Block
{

    /**
     * 获取区块
     * @param string     $name
     * @param array|null $param
     * @return array|mixed|string
     */
    public static function show(string $name, ?array $param = [])
    {
        Block::clearCache('comment.list', 22);
        $cacheKey = md5(self::getUniqId($name, self::getParamId($param)) . '_' . Json::encode($param));
        $data     = DI::Cache()->debugGet($cacheKey, function ($key) use ($name, $param) {
            if (strpos($name, '.')) {
                $var = explode('.', $name);
            } else {
                $var = [$name, 'index'];
            }
            $method = array_pop($var);
            $class  = '\\App\\Block\\' . implode('\\', $var);
            $block  = Loader::instance($class);
            if (!$block || !is_callable([$block, $method])) {
                trigger_error(sprintf('区块 %s 无法加载', $name), E_USER_ERROR);
            }
            $data      = $block->$method($param);
            $cacheTime = $param['cachetime'] ?? $block->getCacheTime();
            if ($cacheTime && $data) {
                DI::Cache()->set($key, $data, $cacheTime);
            }
            return $data;
        });
        //随机数
        if (isset($param['randnum'])) {
            $randnum = Input::param('randnum', 'int', 10, $param);
            if ($randnum > count($data)) {
                $list = [];
                $keys = array_rand($data, $randnum);
                foreach ($keys as $v) {
                    $list[] = $data[$v];
                }
                $data = $list;
            }
        }
        // 定义了模板
        if (isset($param['template'])) {
            View::disableLayout();
            $data = View::make($param['template'], $param);
            View::enableLayout();
        }
        return $data;
    }

    /**
     * 获取参数中的id值
     * @param $param
     * @return string
     */
    protected static function getParamId($param): string
    {
        if (isset($param['id'])) {
            return $param['id'];
        } elseif (isset($param['novelid'])) {
            return $param['novelid'];
        } elseif (isset($param['chapterid'])) {
            return $param['chapterid'];
        } elseif (isset($param['siteid'])) {
            return $param['siteid'];
        } elseif (isset($param['authorid'])) {
            return $param['authorid'];
        } elseif (isset($param['categoryid'])) {
            return $param['categoryid'];
        } elseif (isset($param['typeid'])) {
            return $param['typeid'];
        } else {
            return '0';
        }
    }

    /**
     * 生成区块名的唯一id 用于缓存 便于清理缓存
     * @param string $name
     * @param int    $id
     * @return string
     */
    protected static function getUniqId(string $name, int $id = 0): string
    {
        static $_cache = [];
        $nameUniqId = self::getUniqNameId($name);
        $key        = 'block_uniq_' . $nameUniqId . '_' . $id;
        if (isset($_cache[$key])) {
            return $_cache[$key];
        }
        $data = DI::Cache()->get($key, function ($key) {
            $uniqid = uniqid();
            DI::Cache()->set($key, $uniqid);
            return $uniqid;
        });
        return $_cache[$key] = $data;
    }

    /**
     * 缓存设计
     * @param string $name
     * @return mixed
     */
    protected static function getUniqNameId(string $name)
    {
        static $_cache = [];
        if (isset($_cache['block_uniq_' . $name])) {
            return $_cache['block_uniq_' . $name];
        }
        $key  = 'block_uniq_' . $name;
        $data = DI::Cache()->get($key, function ($key) {
            $uniqid = uniqid();
            DI::Cache()->set($key, $uniqid);
            return $uniqid;
        });
        return $_cache['block_uniq_' . $name] = $data;
    }

    /**
     * 更新区块的唯一
     * @param string $name
     * @param int    $id
     */
    public static function clearCache(string $name, ?int $id = null): void
    {
        if ($id !== null) {
            $nameUniqId = self::getUniqNameId($name);
            DI::Cache()->set('block_uniq_' . $nameUniqId . '_' . $id, uniqid() . uniqid());
        } else {
            DI::Cache()->set('block_uniq_' . $name, uniqid());
        }
    }
}