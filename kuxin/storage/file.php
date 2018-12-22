<?php

namespace Kuxin\Storage;

class File
{

    protected $path = null;
    protected $url = null;

    public function __construct($config)
    {
        $this->path = $config['path'];
        $this->url  = $config['url'] ?? "";
    }

    public function exist($file)
    {
        return is_file($this->getPath($file));
    }

    public function mtime($file)
    {
        return filemtime($this->getPath($file));
    }

    public function write($file, $content)
    {
        $fullfile = $this->getPath($file);
        if (!is_dir(dirname($fullfile))) {
            mkdir(dirname($fullfile), 0755, true);
        }
        return file_put_contents($fullfile, (string)$content);
    }

    public function read($file)
    {
        $fullfile = $this->getPath($file);
        if (is_file($fullfile)) {
            return file_get_contents($fullfile);
        } else {
            return false;
        }
    }

    public function append($file, $content)
    {
        $fullfile = $this->getPath($file);
        if (!is_dir(dirname($fullfile))) {
            mkdir(dirname($fullfile), 0755, true);
        }
        return file_put_contents($fullfile, (string)$content, FILE_APPEND);
    }

    public function remove($file)
    {
        $file = $this->getPath($file);
        if (is_file($file)) {
            //删除文件
            return unlink($file);
        } elseif (is_dir($file)) {
            //删除目录
            $handle = opendir($file);
            while (($filename = readdir($handle)) !== false) {
                if ($filename !== '.' && $filename !== '..') {
                    $this->remove($file . '/' . $filename);
                }
            }
            closedir($handle);
            return rmdir($file);
        }
    }

    public function getUrl($file)
    {
        $file = ($file{0} === '/' || $file{1} == ':') ? str_replace($this->path, '', $file) : $file;
        return rtrim($this->url, '/') . '/' . $file;
    }

    public function getPath($file)
    {
        return ($file{0} === '/' || $file{1} == ':') ? $file : $this->path . '/' . ltrim($file, '/');
    }

    public function error()
    {
        return '';
    }
}