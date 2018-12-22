<?php

namespace Kuxin\Oauth;

use Kuxin\Helper\Http;

class Weixin extends Oauth{
    /**
     * 获取requestCode的api接口
     *
     * @var string
     */
    protected $getRequestCodeURL = 'https://open.weixin.qq.com/connect/qrconnect';
    
    /**
     * 获取access_token的api接口
     *
     * @var string
     */
    protected $getAccessTokenURL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    
    /**
     * 获取request_code的额外参数,可在配置中修改 URL查询字符串格式
     *
     * @var array
     */
    protected $authorizeParam = [
        //'scope'=>'all'
        //'forcelogin' => 'true',
    ];
    
    /**
     * 获取accesstoekn时候的附加参数
     *
     * @var array
     */
    protected $getTokenParam = [
    ];
    
    
    /**
     * API根路径
     *
     * @var string
     */
    protected $apiBase = 'https://api.weixin.qq.com/';
    
    /**
     * 构造函数
     *
     * @param array $config
     * @param null  $token
     */
    public function __construct(array $config, $token = null)
    {
        parent::__construct($config, $token);
        $this->getTokenParam = [
            'appid'  => $this->appid,
            'secret' => $this->appsecret,
        ];
    }
    
    /**
     * 组装接口调用参数 并调用接口
     *
     * @param  string $api    微博API
     * @param  array  $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @param  bool   $multi
     * @return string json
     */
    public function call($api, $param = [], $method = 'GET', $multi = false)
    {
        /* 腾讯QQ调用公共参数 */
        $params = [
            'access_token' => $this->token,
        ];
        $params = array_merge($params, $param);
        $data   = Http::get($this->apiBase . $api, $params);
        return json_decode($data, true);
    }
    
    /**
     * 解析access_token方法请求后的返回值
     *
     * @param $result
     * @return mixed
     * @throws \Exception
     */
    protected function parseToken($result)
    {
        $data = json_decode($result, true);
        if (isset($data['access_token'])) {
            $this->token  = $data['access_token'];
            $this->openid = $data['openid'];
            return [
                'openid'  => $this->openid,
                'token'   => $this->token,
                'expires' => $data['expires_in'],
                'refresh' => $data['refresh_token'],
            ];
        } else
            return "获取 ACCESS_TOKEN 出错：{$result}";
    }
    
    /**
     * 获取openid
     *
     * @return mixed
     * @throws \Exception
     */
    public function getOpenId()
    {
        if ($this->openid) return $this->openid;
        return false;
    }
    
    /**
     * 获取用户信息
     *
     * @return array
     */
    public function getInfo()
    {
        $data = $this->call('sns/userinfo', ['openid' => $this->getOpenId()]);
        return [
            'id'     => $this->openid,
            'name'   => $data['nickname'],
            'gender' => $data['sex'],
            'avatar' => $data['headimgurl'],
        ];
    }
}