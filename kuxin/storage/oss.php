<?php

namespace Kuxin\Storage;

use Kuxin\Helper\File;
use Kuxin\Helper\Http;

class Oss
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
        $this->host            = $config['bucket'] . '.' . $config['endpoint'];
        $this->api             = 'http://' . $this->host . '/';
        $this->preUrl          = $config['url'];
        $this->path            = $config['path'];
    }

    public function getPath($object)
    {
        return $object;
    }

    public function exist($object)
    {
        $data = $this->getObjectMeta($object);

        return !empty($data['last-modified']);
    }

    public function mtime($object)
    {
        $data = $this->getObjectMeta($object);

        return $data['x-oss-meta-mtime'] ?? (isset($data['last-modified']) ? strtotime($data['last-modified']) : 0);
    }


    public function read($object)
    {
        $path                    = $this->path . '/' . $object;
        $method                  = 'GET';
        $options[CURLOPT_HEADER] = 1;

        $headers                  = $this->genHeaders($object);
        $sign                     = $this->sign($method, $headers, '/' . $this->bucket . $path);
        $headers['Authorization'] = "OSS {$this->accessKeyId}:{$sign}";
        $url                      = $this->api . $path;
        $res                      = Http::curl($url, [], $method, $headers, $options);
        $headers                  = Http::parse_headers($res);
        if ($headers['x-oss-meta-mtime']) {
            return Http::parse_content($res);
        } else {
            return false;
        }
    }

    public function write($object, $content)
    {
        $path                     = $this->path . '/' . $object;
        $method                   = 'PUT';
        $options[CURLOPT_HEADER]  = 1;
        $headers                  = $this->genHeaders($object);
        $headers['Content-MD5']   = base64_encode(md5($content, true));
        $sign                     = $this->sign($method, $headers, '/' . $this->bucket . $path);
        $headers['Authorization'] = "OSS {$this->accessKeyId}:{$sign}";
        $url                      = $this->api . $path;
        $res                      = Http::curl($url, $content, $method, $headers, $options);

        return strpos($res, '200 OK');
    }

    public function append($object, $content)
    {
        $data                       = $this->getObjectMeta($object);
        $path                       = $this->path . '/' . $object . '?append&position=0' . $data['content-length'];
        $method                     = 'POST';
        $options[CURLOPT_HEADER]    = 1;
        $headers                    = $this->genHeaders($object);
        $headers['Content-MD5']     = base64_encode(md5($content, true));
        $headers['Accept-Encoding'] = '';
        $sign                       = $this->sign($method, $headers, '/' . $this->bucket . $path);
        $headers['Authorization']   = "OSS {$this->accessKeyId}:{$sign}";
        $url                        = $this->api . $path;
        $res                        = Http::curl($url, $content, $method, $headers, $options);

        return strpos($res, '200 OK');
    }

    public function remove($object)
    {
        $path                     = $this->path . '/' . $object;
        $method                   = 'DELETE';
        $options[CURLOPT_HEADER]  = 1;
        $headers                  = $this->genHeaders($object);
        $sign                     = $this->sign($method, $headers, '/' . $this->bucket . $path);
        $headers['Authorization'] = "OSS {$this->accessKeyId}:{$sign}";
        $url                      = $this->api . $path;
        $res                      = Http::curl($url, [], $method, $headers, $options);
        $headers                  = Http::parse_headers($res);
        if ($headers['x-oss-meta-mtime']) {
            return Http::parse_content($res);
        } else {
            return false;
        }
    }

    public function getUrl($object)
    {
        return $this->preUrl . $this->path . '/' . $object;
    }

    public function error()
    {
        return '';
    }

    private function getObjectMeta($object)
    {
        $path                    = $this->path . '/' . $object . '?objectMeta';
        $method                  = 'HEAD';
        $options[CURLOPT_HEADER] = 1;

        $headers                  = $this->genHeaders($object);
        $sign                     = $this->sign($method, $headers, '/' . $this->bucket . $path);
        $headers['Authorization'] = "OSS {$this->accessKeyId}:{$sign}";
        $url                      = $this->api . $path;
        $res                      = Http::curl($url, [], $method, $headers, $options);

        return Http::parse_headers($res);
    }

    private function sign($method, $headers, $object)
    {
        $param   = [];
        $param[] = $method;
        $param[] = $headers['Content-MD5'] ?? "";
        $param[] = $headers['Content-Type'] ?? "";
        $param[] = $headers['Date'];
        //$param[] = $headers['Host'];
        $param[] = $object;

        return base64_encode(hash_hmac('sha1', join("\n", $param), $this->accessKeySecret, true));
    }

    private function genHeaders($object)
    {
        return [
            'Date'         => gmdate('D, d M Y H:i:s \G\M\T'),
            'Host'         => $this->host,
            'Content-Type' => File::getMimeByName($object),
        ];
    }
}