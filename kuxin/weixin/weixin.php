<?php
namespace Kuxin\Weixin;

use Kuxin\Config;
use Kuxin\DI;
use Kuxin\Helper\Http;
use Kuxin\Loader;

class Weixin
{
    
    /**
     * 是否需要access_token
     *
     * @var bool
     */
    protected $token = false;
    
    /**
     * appid
     *
     * @var string
     */
    protected $appId;
    
    /**
     * @var string
     */
    protected $appSecret;
    
    /**
     * @var string
     */
    protected $access_token;
    
    const API_TOKEN_GET = 'https://api.weixin.qq.com/cgi-bin/token';
    
    public function __construct($appId, $appSecret)
    {
        $this->appId     = $appId ?: Config::get('weixin.appid');
        $this->appSecret = $appSecret ?: Config::get('weixin.appsecret');
    }
    
    /**
     * @param $appId
     * @param $appSecret
     * @return static
     */
    public static function I($appId = null, $appSecret = null)
    {
        $class = static::class;
        return Loader::instance($class, [$appId, $appSecret]);
    }
    
    
    public function getToken($forceRefresh = 0)
    {
        $cacheKey = 'kuxin.weixin.accesstoken_' . $this->appId;
        $data     = DI::Cache()->get($cacheKey);
        if ($forceRefresh || empty($data)) {
            $token = $this->_getTokenFromServer();
            // XXX: T_T... 7200 - 1500
            DI::Cache()->set($cacheKey, $token['access_token'], $token['expires_in'] - 1500);
            return $token['access_token'];
        }
        return $data;
    }
    
    protected function _getTokenFromServer()
    {
        $params = [
            'appid'      => $this->appId,
            'secret'     => $this->appSecret,
            'grant_type' => 'client_credential',
        ];
        $token  = $this->parseJSON(Http::get(self::API_TOKEN_GET, $params));
        if (empty($token['access_token'])) {
            trigger_error('Request AccessToken fail. response: ' . json_encode($token, JSON_UNESCAPED_UNICODE), E_USER_ERROR);
        }
        return $token;
    }
    
    protected function parseJSON($data)
    {
        if ($data{0} == '{') {
            return json_decode($data, true);
        } else {
            return null;
        }
    }
    
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str   = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}