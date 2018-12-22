<?php

namespace Kuxin;

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
     * @param string $name
     * @param array|null $param
     * @return array|mixed|string
     */
    public static function show(string $name, ?array $param = [])
    {
        $cacheKey = md5(self::getUniqId($name, $param['id'] ?? 0) . '_' . json_encode($param));
        $data     = DI::Cache()->debugGet($cacheKey, function ($key) use ($name, $param) {
            if (strpos($name, '.')) {
                $var = explode('.', $name);
            } else {
                $var = [$name, 'run'];
            }
            $method = array_pop($var);
            $class  = '\\App\\Block\\' . implode('\\', $var);
            $block  = Loader::instance($class);
            if (!$block || !method_exists($block, $method)) {
                trigger_error(sprintf('区块 %s 无法加载', $name), E_USER_ERROR);
            }
            $cacheTime = $param['cachetime'] ?? Config::get('cache.block_time', 600);
            $data      = $block->$method($param);
            DI::Cache()->set($key, $data, $cacheTime);
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
     * 生成区块名的唯一id 用于缓存
     * @param string $name
     * @param int $id
     * @return string
     */
    protected static function getUniqId(string $name, int $id = 0): string
    {
        return DI::Cache()->get('block_uniq_' . $name . '_' . $id, function ($key) use ($name) {
            $nameUniqId = self::getUniqNameId($name);
            $uniqid     = uniqid();
            DI::Cache()->set($key, $nameUniqId . $uniqid);
            return $nameUniqId . $uniqid;
        });
    }

    protected static function getUniqNameId(string $name)
    {
        return DI::Cache()->get('block_uniq_' . $name, function ($key) {
            $uniqid = uniqid();
            DI::Cache()->set($key, $uniqid);
            return $uniqid;
        });
    }

    /**
     * 更新区块的唯一
     * @param string $name
     * @param int $id
     */
    public static function clearCache(string $name, int $id = 0): void
    {
        $nameUniqId = DI::Cache()->get('block_uniq_' . $name, function ($key) {
            $uniqid = uniqid();
            DI::Cache()->set($key, $uniqid);
            return $uniqid;
        });
        if ($id) {
            DI::Cache()->set('block_uniq_' . $name . '_' . $id, $nameUniqId . uniqid());
        } else {
            DI::Cache()->set('block_uniq_' . $name, uniqid());
        }
    }
}