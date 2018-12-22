<?php

namespace Kuxin\Cache;


use Kuxin\Config;
use Kuxin\Helper\Serialize;

class Memcached
{

    /**
     * @var \Memcached
     */
    protected $handler;

    /**
     * @var string 缓存前缀
     */
    protected $prefix = '';

    public function __construct(array $option)
    {
        if (!extension_loaded('memcached')) {
            trigger_error('您尚未安装memcached扩展', E_USER_ERROR);
        }
        $this->handler = new \Memcached();
        $this->handler->addServer($option['host'] ?? '127.0.0.1', $option['port']??'11211');
        $this->prefix = $option['prefix'] ?? Config::get('cache.prefix', '');
    }

    public function set(string $key, $value, int $time = 0)
    {
        return $this->handler->set($this->prefix . $key, Serialize::encode($value), $time);
    }

    public function get(string $key)
    {
        $return = $this->handler->get($this->prefix . $key);
        if ($return === false) {
            return null;
        } elseif (is_string($return)) {
            return Serialize::decode($return);
        } else {
            return $return;
        }
    }

    public function remove(string $key)
    {
        return $this->handler->delete($this->prefix . $key);
    }

    public function inc(string $key, int $num = 1)
    {
        $key = $this->prefix . $key;
        if ($this->handler->get($key)) {
            return $this->handler->increment($key, $num);
        }
        return $this->handler->set($key, $num);
    }

    public function dec(string $key, int $num = 1)
    {
        $key = $this->prefix . $key;
        if ($this->handler->get($key)) {
            return $this->handler->decrement($key, $num);
        } else {
            return $this->handler->set($key, 0);
        }
    }

    public function clear()
    {
        $this->handler->flush();
    }
}