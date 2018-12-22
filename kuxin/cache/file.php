<?php

namespace Kuxin\Cache;

use Kuxin\Config;
use Kuxin\Helper\Serialize;

class File
{

    /**
     * 缓存路径
     *
     * @var string
     */
    public $path = '';

    /**
     * @var string 缓存前缀
     */
    protected $prefix = '';


    public function __construct($option)
    {
        $this->path   = $option['path'] ?? KX_ROOT . '/storage/cache';
        $this->prefix = $option['prefix'] ?? Config::get('cache.prefix', '');
    }

    public function set(string $key, $value, int $time = 0)
    {
        $file         = $this->key2file($key);
        $data['data'] = $value;
        $data['time'] = ($time == 0) ? 0 : ($_SERVER['REQUEST_TIME'] + $time);
        return file_put_contents($file, Serialize::encode($data));
    }

    public function get(string $key)
    {
        $file = $this->key2file($key);
        if (is_file($file)) {
            $data = Serialize::decode(file_get_contents($file));
            if ($data && ($data['time'] > 0 && $data['time'] < $_SERVER['REQUEST_TIME'])) {
                $this->remove($key);
                return null;
            }
            return $data['data'];
        } else {
            return null;
        }
    }

    public function remove(string $key)
    {
        $file = $this->key2file($key);
        if (is_file($file))
            return unlink($file);
        return false;
    }

    public function inc(string $key, int $num = 1)
    {
        $data = $this->get($key);
        if ($data) {
            $data += $num;
            $this->set($key, $data);
            return $data;
        }
        return false;
    }

    public function dec(string $key, int $num = 1)
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

    }

    protected function key2file(string $key)
    {
        if (is_array($key)) {
            $key = Serialize::encode($key);
        }
        $key  = md5($key);
        $path = $this->path . '/' . $key{0} . '/' . $key{1} . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $file = $path . $key . '.php';
        return $file;
    }
}