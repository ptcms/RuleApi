<?php

namespace App\Rule;

use Kuxin\Config;
use Kuxin\DI;
use Kuxin\Helper\Arr;
use Kuxin\Helper\Collect;
use Kuxin\Helper\Http;
use Kuxin\Helper\Json;

abstract class Kernel
{
    protected $useProxy = 0;

    public function __construct()
    {
        if ($this->useProxy) {
            $this->setProxy();
        }
        $userAgents = Config::get('useragents');
        Config::set('http.user_agent', Arr::rand($userAgents));
    }

    protected function setProxy()
    {
        $info = DI::Cache()->get('http.proxy.info');
        $info = json_decode($info, true);
        if ($info && $info['expire'] > time()) {
            Config::set('http.proxy.power', 1);
            Config::set('http.proxy.host', $info['ip']);
            Config::set('http.proxy.port', $info['port']);
            Config::set('http.proxy.type', CURLPROXY_SOCKS5);
        }
    }

    /**
     * @param int $page
     * @reutrn array()
     */
    abstract public function getlist($page);

    /**
     * @param $novelid
     * @return array
     */
    abstract public function getinfo($novelid);

    /**
     * @param $novelid
     * @return array
     */
    abstract public function getdir($novelid);

    /**
     * @param $novelid
     * @return array
     */
    public function getDown($novelid)
    {
        return [];
    }

    /**
     * @param $novelid
     * @return array
     */
    public function getSearch($novelname, $author)
    {
        return [];
    }

    /**
     * @param $param
     * @return array
     */
    abstract public function getchapter($param);

    //获取json数据
    protected function getjson($url)
    {
        return Json::decode(trim(Http::get($url)));
    }

    public function getDownFileSize($url)
    {
        $content = (Http::get($url, [], [], [
            CURLOPT_HEADER => 1,
            CURLOPT_NOBODY => 1,
        ]));
        if (strpos($content, 'Content-Length')) {
            return Collect::getMatch('Content-Length:\s*(\d+)\s+', $content);
        }
        return 0;
    }
}