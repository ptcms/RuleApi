<?php

namespace Kuxin;

/**
 * Class Storage
 *
 * @package Kuxin
 * @author  Pakey <pakey@qq.com>
 */
class Storage
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Kuxin\Storage\File
     */
    protected $handler;

    public function __construct(array $config)
    {
        $this->config = $config;
        $class        = 'Kuxin\\storage\\' . $config['driver'];
        return $this->handler = Loader::instance($class, [$config['option']]);
    }

    /**
     * @param $file
     * @return bool
     */
    public function exist(string $file)
    {
        return $this->handler->exist($file);
    }

    /**
     * @param $file
     * @return bool|int
     */
    public function mtime(string $file)
    {
        return $this->handler->mtime($file);
    }

    /**
     * @param $file
     * @param $content
     * @return bool|int
     */
    public function write(string $file, string $content)
    {
        return $this->handler->write($file, $content);
    }

    /**
     * @param $file
     * @return bool|string
     */
    public function read(string $file)
    {
        return $this->handler->read($file);
    }

    /**
     * @param $file
     * @param $content
     * @return bool|int
     */
    public function append(string $file, string $content)
    {
        return $this->handler->append($file, $content);
    }

    /**
     * @param $file
     * @return bool
     */
    public function remove(string $file)
    {
        return $this->handler->remove($file);
    }

    /**
     * @param $file
     * @return string
     */
    public function getUrl(string $file)
    {
        return $this->handler->getUrl($file);
    }

    /**
     * @param $file
     * @return string
     */
    public function getPath(string $file)
    {
        return $this->handler->getPath($file);
    }

    /**
     * @return string
     */
    public function error()
    {
        return $this->handler->error();
    }
}