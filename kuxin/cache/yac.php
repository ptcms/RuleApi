<?php

namespace Kuxin\Cache;

use Kuxin\Config;
use Kuxin\Helper\Serialize;

class Yac
{

    /**
     * @var \Yac
     */
    protected $handler;

    /**
     * @var string 缓存前缀
     */
    protected $prefix = '';


    public function __construct($option)
    {
        if (!extension_loaded('yac')) {
            trigger_error('您尚未安装yac扩展', E_USER_ERROR);
        }
        $this->prefix  = $option['prefix'] ?? Config::get('cache.prefix', '');
        $this->handler = new \Yac($this->prefix);
    }

    public function set($key, $value, $time = 0)
    {
        return $this->handler->set($key, Serialize::encode($value), $time);
    }

    public function get($key)
    {
        $return = $this->handler->get($key);
        if ($return === false) {
            return null;
        } elseif (is_string($return)) {
            return Serialize::decode($return);
        } else {
            return $return;
        }
    }

    public function remove($key)
    {
        return $this->handler->delete($key);
    }

    public function inc($key, $num = 1)
    {
        $data = $this->get($key);
        if ($data) {
            $data += $num;
            $this->set($key, $data);
            return $data;
        }
        return false;
    }

    public function dec($key, $num = 1)
    {
        $data = $this->get($key);
        if ($data) {
            $data -= $num;
            $this->set($key, $data);
            return $data;
        }
        return false;
    }

    public function clear()
    {
        Yac::flush();
    }
}