<?php

namespace Kuxin\Weixin;
use Kuxin\DI;
use Kuxin\Helper\Http;
use Kuxin\Helper\Url;
use Kuxin\Response;

/**
 * 微信 JSSDK.
 */
class Js extends Weixin
{

    /**
     * 当前URL.
     *
     * @var string
     */
    protected $url;

    const API_TICKET = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi';

    /**
     * 获取JSSDK的配置数组.
     *
     * @param array $APIs
     * @param bool  $debug
     * @param bool  $json
     *
     * @return string|array
     */
    public function config(array $APIs, $debug = false,  $json = true)
    {
        $signPackage = $this->getSignaturePackage($debug);
        $base        = array(
            'debug' => $debug,
        );
        $config      = array_merge($base, $signPackage, array('jsApiList' => $APIs));

        return $json ? json_encode($config) : $config;
    }

    /**
     * 获取数组形式的配置.
     *
     * @param array $APIs
     * @param bool  $debug
     *
     * @return array
     */
    public function getConfigArray(array $APIs, $debug = false)
    {
        return $this->config($APIs, $debug, false);
    }

    /**
     * 获取jsticket.
     *
     * @return string
     */
    public function getTicket()
    {
        $key = 'kuxin.weixin.jsapi_ticket.' . $this->appId;

        $data = DI::Cache()->get($key);
        if (!$data || empty($data['ticket'])) {
            $token = $this->getToken();
            $data  = $this->parseJSON(Http::get(self::API_TICKET, ['access_token' => $token]));
            if ($data && !empty($data['ticket'])) {
                DI::Cache()->set($key, $data, $data['expires_in']/10);
            } else {
                $data['ticket'] = null;
            }

        }
        return $data['ticket'];
    }

    /**
     * 签名.
     *
     * @param bool $debug
     * @param string $url
     * @param string $nonce
     * @param int    $timestamp
     *
     * @return array
     */
    public function getSignaturePackage($debug=false,$url = null, $nonce = null, $timestamp = null)
    {
        $url       = $url ? $url : $this->getUrl();
        $nonce     = $nonce ? $nonce : $this->getNonce();
        $timestamp = $timestamp ? $timestamp : time();
        $ticket    = $this->getTicket();
        $sign = [
            'appId'     => $this->appId,
            'nonceStr'  => $nonce,
            'timestamp' => $timestamp,
            'signature' => $this->getSignature($ticket, $nonce, $timestamp, $url),
        ];
        if($debug){
            $sign['url']=$url;
            $sign['ticket']=$ticket;
        }
        return $sign;
    }

    /**
     * 生成签名.
     *
     * @param string $ticket
     * @param string $nonce
     * @param int    $timestamp
     * @param string $url
     *
     * @return string
     */
    public function getSignature($ticket, $nonce, $timestamp, $url)
    {
        return sha1("jsapi_ticket={$ticket}&noncestr={$nonce}&timestamp={$timestamp}&url={$url}");
    }

    /**
     * 设置当前URL.
     *
     * @param string $url
     *
     * @return Js
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * 获取当前URL.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->url) {
            return $this->url;
        }

        return Url::weixin();
    }

    /**
     * 获取随机字符串.
     *
     * @return string
     */
    public function getNonce()
    {
        return uniqid('kxcms_');
    }
}
