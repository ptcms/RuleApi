<?php

namespace Kuxin\Oauth;

use Kuxin\Config;
use Kuxin\Loader;

abstract class Oauth{
    
    /**
     * 申请应用时分配的app_key
     *
     * @var string
     */
    protected $appid = '';
    
    /**
     * 申请应用时分配的 app_secret
     *
     * @var string
     */
    protected $appsecret = '';
    
    /**
     * 授权类型 response_type 目前只能为code
     *
     * @var string
     */
    protected $responseType = 'code';
    
    /**
     * grant_type 目前只能为 authorization_code
     *
     * @var string
     */
    protected $grantType = 'authorization_code';
    
    /**
     * 获取request_code的额外参数
     *
     * @var array
     */
    protected $authorizeParam = [];
    /**
     * 获取accesstoekn时候的附加参数
     *
     * @var array
     */
    protected $getTokenParam = [];
    
    /**
     * 获取request_code请求的URL
     *
     * @var string
     */
    protected $getRequestCodeURL = '';
    
    /**
     * 获取access_token请求的URL
     *
     * @var string
     */
    protected $getAccessTokenURL = '';
    
    /**
     * API根路径
     *
     * @var string
     */
    protected $apiBase = '';
    
    /**
     * 授权后获取到的TOKEN信息
     *
     * @var array
     */
    protected $token = null;
    /**
     * 授权后的用户id
     *
     * @var null
     */
    protected $openid = null;

    /**
     * 构造函数
     *
     * @param array $config
     * @param null  $token
     */
    public function __construct(array $config, $token = null)
    {
        $this->appid     = $config['appid'];
        $this->appsecret = $config['appsecret'];
        $this->token     = $token;
        Config::set('user_agent', '');
    }
    
    /**
     * @param      $type
     * @param null $token
     * @return static
     */
    public static function getInstance($type, $token = null)
    {
        $config['appid']        = Config::get("oauth_{$type}_appid");
        $config['appsecret']    = Config::get("oauth_{$type}_appsecret");
        $classname              = 'Driver_Oauth_' . $type;
        return Loader::instance($classname, [$config, $token]);
    }
    
    /**
     * 前往认证页
     *
     * @param $url
     * @return string
     */
    public function authorize($url)
    {
        $param = [
            "response_type" => $this->responseType,
            "client_id"     => $this->appid,
            "redirect_uri"  => $url,
            "state"         => time(),
        ];
        $param = array_merge($param, $this->authorizeParam);
        if (strpos($this->getRequestCodeURL, '?') === false) {
            return $this->getRequestCodeURL . '?' . http_build_query($param);
        } else {
            return $this->getRequestCodeURL . '&' . http_build_query($param);
        }
    }
    
    /**
     * 获取access token
     *
     * @param $code
     * @param $url
     * @return string
     */
    public function getAccessToken($code, $url)
    {
        $param    = [
            "grant_type"    => $this->grantType,
            "client_id"     => $this->appid,
            "redirect_uri"  => $url,
            "client_secret" => $this->appsecret,
            "code"          => $code,
        ];
        $param    = array_merge($param, $this->getTokenParam);
        $response = http::post($this->getAccessTokenURL, http_build_query($param));
        return $this->parseToken($response);
    }
    
    
    /**
     * @param string $api    接口名
     * @param string $param  参数
     * @param string $method 是否POST
     * @param bool   $multi  是否上传文件
     * @return array
     */
    abstract protected function call($api, $param = '', $method = 'GET', $multi = false);
    
    /**
     * 抽象方法 解析access_token方法请求后的返回值
     *
     * @param 待处理内容
     * @return string
     */
    abstract protected function parseToken($result);
    
    /**
     * 抽象方法  获取当前授权用户的标识
     *
     * @return mixed
     */
    abstract public function getOpenId();
    
    /**
     * 获取用户信息
     *
     * @return mixed
     */
    abstract public function getInfo();
}
