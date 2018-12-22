<?php

namespace app\controller;

use app\controller\common\common;
use app\model\User;
use Kuxin\Cache;
use Kuxin\Config;
use Kuxin\Controller;
use Kuxin\DI;
use Kuxin\Helper\Http;
use Kuxin\Helper\Json;
use Kuxin\Input;
use Kuxin\Log;
use Kuxin\Response;

class Index extends Controller
{

    public function index()
    {
        return 'hello ptcms!';
    }

    public function proxy()
    {
        Config::set('http.user_agent', 'curl/7.54.0');
        $info = DI::Cache()->get('http.proxy.info');
        $info = json_decode($info, true);
        if ($info && $info['expire'] > time()) {
            Config::set('http.proxy.power', 1);
            Config::set('http.proxy.host', $info['ip']);
            Config::set('http.proxy.port', $info['port']);
            Config::set('http.proxy.type', CURLPROXY_SOCKS5);
            $urls = ['http://myip.ipip.net'];
            foreach ($urls as $url) {
                $res = Http::get($url);
                if (strpos($res, $info['ip'])) {
                    echo(date('Y-m-d H:i:s ') . '代理可用 [' . $info['ip'] . ':' . $info['port'] . '] 过期时间 [ ' . date('Y-m-d H:i:s', $info['expire']) . ' ] ' . trim($res) . PHP_EOL);
                    exit;
                }
            }
            echo date('Y-m-d H:i:s ') . '代理不可用，尝试获取新代理 [' . $info['ip'] . ':' . $info['port'] . '] ' . trim($res), PHP_EOL;
        } else {
            echo date('Y-m-d H:i:s ') . '代理过期', PHP_EOL;
        }
        for ($i = 1; $i <= 5; $i++) {
            $url  = 'http://webapi.http.zhimacangku.com/getip?num=1&type=2&pro=&city=0&yys=0&port=2&pack=35657&ts=1&ys=0&cs=0&lb=1&sb=0&pb=5&mr=1&regions=';

            $data = file_get_contents($url);
            $data = json_decode($data, true);
            if (empty($data['data']['0']['ip'])) {
                sleep(2);
                continue;
            }
            Config::set('http.proxy.power', 1);
            Config::set('http.proxy.host', $data['data']['0']['ip']);
            Config::set('http.proxy.port', $data['data']['0']['port']);
            Config::set('http.proxy.type', CURLPROXY_SOCKS5);
            $urls = ['http://myip.ipip.net'];
            foreach ($urls as $url) {
                $res = Http::get($url);
                if (strpos($res, $data['data']['0']['ip'])) {
                    $expire = strtotime($data['data']['0']['expire_time']) - 130;
                    DI::Cache()->set('http.proxy.info', json_encode([
                        'ip'     => $data['data']['0']['ip'],
                        'port'   => $data['data']['0']['port'],
                        'expire' => $expire,
                    ]), 3600);
                    exit(date('Y-m-d H:i:s ') . '获取新代理成功 [' . $data['data']['0']['ip'] . ':' . $data['data']['0']['port'] . '] 过期时间 [ ' . date('Y-m-d H:i:s', $expire) . ' ] ' . PHP_EOL);
                }
            }
            echo date('Y-m-d H:i:s ') . '获取新代理不可用，尝试获取新代理 [' . $data['data']['0']['ip'] . ':' . $data['data']['0']['port'] . ']' . trim($res), PHP_EOL;
            sleep(3);
        }
        exit(date('Y-m-d H:i:s ') . '无可用代理' . PHP_EOL);
    }
}
