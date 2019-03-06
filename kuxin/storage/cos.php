<?php

namespace Kuxin\Storage;

use Kuxin\Helper\Http;

/**
 * 腾讯云云存储
 * Class Cos
 * @package Kuxin\Storage
 * @author  Pakey <pakey@qq.com>
 */
class Cos
{

    public function __construct($config)
    {
        if (empty($config['key'])) {
            trigger_error('access key 未设置');
        }
        if (empty($config['secret'])) {
            trigger_error('access secret 未设置');
        }
        if (empty($config['bucket'])) {
            trigger_error('bucket 未设置');
        }
        if (empty($config['endpoint'])) {
            trigger_error('endpoint 未设置');
        }
        if (empty($config['url'])) {
            trigger_error('访问域名 未设置');
        }

        $this->accessKeyId     = $config['key'];
        $this->accessKeySecret = $config['secret'];
        $this->bucket          = $config['bucket'];
        $this->host            = $config['bucket'] . '.cos.' . $config['endpoint'];
        $this->api             = 'http://' . $this->host;
        $this->preUrl          = $config['url'];
        $this->path            = '/' . ltrim('/' . $config['path'] ?? '', '/');
    }

    public function getPath($object)
    {
        return $object;
    }

    public function exist($object)
    {
        $path = $this->path . '/' . $object;
        $sign = $this->sign('HEAD', $path, $this->host);
        $res  = Http::curl($this->api . $path, [], 'HEAD', ['Authorization' => $sign], [CURLOPT_HEADER => 1]);
        return strpos($res, ' 200 OK');
    }

    public function mtime($object)
    {
        $path = $this->path . '/' . $object;
        $sign = $this->sign('HEAD', $path, $this->host);
        $res  = Http::curl($this->api . $path, [], 'HEAD', ['Authorization' => $sign], [CURLOPT_HEADER => 1]);
        $data = Http::parse_headers($res);
        return isset($data['last-modified']) ? strtotime($data['last-modified']) : 0;
    }


    public function read($object)
    {
        $path = $this->path . '/' . $object;
        $sign = $this->sign('GET', $path, $this->host);
        return Http::curl($this->api . $path, [], 'GET', ['Authorization' => $sign]);
    }

    public function write($object, $content)
    {
        $path = $this->path . '/' . $object;
        $sign = $this->sign('PUT', $path, $this->host);
        $res  = Http::curl($this->api . $path, $content, 'PUT', ['Authorization' => $sign]);
        return $res === "";
    }

    public function append($object, $content)
    {
        if ($this->exist($object)) {
            $content = $this->read($object) . $content;
        }
        return $this->write($object, $content);
    }

    public function remove($object)
    {
        $path = $this->path . '/' . $object;
        $sign = $this->sign('DELETE', $path, $this->host);
        $res  = Http::curl($this->api . $path, [], 'DELETE', ['Authorization' => $sign]);
        return $res === '';
    }

    public function getUrl($object)
    {
        return $this->preUrl . $this->path . '/' . $object;
    }

    public function error()
    {
        return '';
    }


    private function sign($method, $path, $host)
    {
        $signTime         = (string)(time() - 60) . ';' . (string)(time() + 3600);
        $httpString       = strtolower($method) . "\n" . urldecode($path) .
            "\n\nhost=" . $host . "\n";
        $sha1edHttpString = sha1($httpString);
        $stringToSign     = "sha1\n$signTime\n$sha1edHttpString\n";
        $signKey          = hash_hmac('sha1', $signTime, $this->accessKeySecret);
        $signature        = hash_hmac('sha1', $stringToSign, $signKey);
        $authorization    = 'q-sign-algorithm=sha1&q-ak=' . $this->accessKeyId .
            "&q-sign-time=$signTime&q-key-time=$signTime&q-header-list=host&q-url-param-list=&" .
            "q-signature=$signature";
        return $authorization;
    }
}