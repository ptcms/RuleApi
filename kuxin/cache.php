<?php
/**
 * @Author: 杰少Pakey
 * @Email : Pakey@qq.com
 * @File  : cache.php
 */

namespace Kuxin;

/**
 * 缓存
 * Class Cache
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Cache
{

    /**
     * @var \Kuxin\Cache\Memcache
     */
    protected $handler = null;


    /**
     * Cache constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $class         = '\\Kuxin\\Cache\\' . $config['driver'];
        $this->handler = Loader::instance($class, [$config['option']]);
    }

    /**
     * 设置缓存
     *
     * @param     $key
     * @param     $value
     * @param int $time
     */
    public function set(string $key, $value, $time = 0): void
    {
        Registry::setInc('_cacheWrite');
        $this->handler->set($key, $value, $time);
    }

    /**
     * 获取缓存
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        Registry::setInc('_cacheRead');
        $result = $this->handler->get($key);
        if ($result === null) {
            $result = (is_callable($default) ? $default($key) : $default);
        } else {
            Registry::setInc('_cacheHit');
        }
        return $result;
    }

    /**
     * debug模式来获取缓存，debug模式不取缓存
     *
     * @param       $key
     * @param mixed $default
     * @return mixed
     */
    public function debugGet(string $key, $default = null)
    {
        Registry::setInc('_cacheRead');
        $result = Config::get('app.debug') ? null : $this->handler->get($key);
        if ($result === null) {
            $result = (is_callable($default) ? $default($key) : $default);
        } else {
            Registry::setInc('_cacheHit');
        }
        return $result;
    }

    /**
     * 删除缓存
     *
     * @param $key
     * @return bool
     */
    public function remove(string $key): bool
    {
        return $this->handler->remove($key);
    }

    /**
     * 缓存计数 增加
     *
     * @param     $key
     * @param int $len
     * @return mixed|bool|int
     */
    public function inc(string $key, int $len = 1)
    {
        return $this->handler->inc($key, $len);
    }

    /**
     * 缓存计数 减少
     *
     * @param     $key
     * @param int $len
     * @return mixed|bool|int
     */
    public function dec(string $key, int $len = 1)
    {
        return $this->handler->dec($key, $len);
    }

    /**
     * 清空缓存
     */
    public function clear(): void
    {
        $this->handler->clear();
    }

    /**
     * @param $method
     * @param $args
     */
    public function __call($method, $args)
    {
        if (method_exists($this->handler, $method)) {
            call_user_func_array([$this->handler, $method], $args);
        } else {
            trigger_error('Cache中不存在的方法');
        }
    }

}


 