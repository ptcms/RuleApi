<?php

namespace Kuxin\Weixin;
use Kuxin\Helper\Http;
use Kuxin\Helper\Url;
use Kuxin\Input;

/**
 * 网页授权
 * Class Auth
 *
 * 1、以snsapi_base为scope发起的网页授权，是用来获取进入页面的用户的openid的，并且是静默授权并自动跳转到回调页的。用户感知的就是直接进入了回调页（往往是业务页面）
 * 2、以snsapi_userinfo为scope发起的网页授权，是用来获取用户的基本信息的。但这种授权需要用户手动同意，并且由于用户同意过，所以无须关注，就可在授权后获取该用户的基本信息。
 *
 * @package Weixin
 */
class Oauth extends Weixin
{

    public $user;

    protected $lastPermission;

    protected $authorizedUser;

    const API_USER           = 'https://api.weixin.qq.com/sns/userinfo';
    const API_TOKEN_GET      = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    const API_TOKEN_REFRESH  = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
    const API_TOKEN_VALIDATE = 'https://api.weixin.qq.com/sns/auth';
    const API_URL            = 'https://open.weixin.qq.com/connect/oauth2/authorize';


    /**
     * 生成outh URL
     *
     * @param string $to
     * @param string $scope
     * @param string $state
     *
     * @return string
     */
    public function url($to = null, $scope = 'snsapi_userinfo', $state = 'STATE')
    {
        $to !== null || $to = Url::current();
        $params = array(
            'appid'         => $this->appId,
            'redirect_uri'  => $to,
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $state,
        );
        return self::API_URL . '?' . http_build_query($params) . '#wechat_redirect';
    }

    /**
     * 直接跳转
     *
     * @param string $to
     * @param string $scope
     * @param string $state
     */
    public function redirect($to = null, $scope = 'snsapi_userinfo', $state = 'STATE')
    {
        header('Location:' . $this->url($to, $scope, $state));
        exit;
    }


    /**
     * 获取已授权用户
     *
     * @return mixed
     */
    public function user()
    {
        if ($this->authorizedUser
            || !Input::has('state', $_GET)
            || (!$code = Input::get('code','str')) && !Input::has('state', $_GET)
        ) {
            return $this->authorizedUser;
        }
        $permission = $this->getAccessPermission($code);
        
        if ($permission['scope'] !== 'snsapi_userinfo') {
            $user = ['openid' => $permission['openid']];
        } else {
            $user = $this->getUser($permission['openid'], $permission['access_token']);
        }
        
        return $this->authorizedUser = $user;
    }

    /**
     * 通过授权获取用户
     *
     * @param string $to
     * @param string $state
     * @param string $scope
     *
     * @return null
     */
    public function authorize($to = null, $scope = 'snsapi_userinfo', $state = 'STATE')
    {
        if (!Input::has('state', $_GET) && !Input::has('code', $_GET)) {
            $this->redirect($to, $scope, $state);
        }
        return $this->user();
    }

    /**
     * 检查 Access Token 是否有效
     *
     * @param string $accessToken
     * @param string $openId
     *
     * @return boolean
     */
    public function accessTokenIsValid($accessToken, $openId)
    {
        $params = array(
            'openid'       => $openId,
            'access_token' => $accessToken,
        );
        try {
            Http::get(self::API_TOKEN_VALIDATE, $params);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 刷新 access_token
     *
     * @param string $refreshToken
     *
     * @return mixed
     */
    public function refresh($refreshToken)
    {
        $params               = [
            'appid'         => $this->appId,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];
        $permission           = $this->parseJSON(Http::get(self::API_TOKEN_REFRESH, $params));
        $this->lastPermission = array_merge($this->lastPermission, $permission);
        return $permission;
    }

    /**
     * 获取用户信息
     *
     * @param string $openId
     * @param string $accessToken
     *
     * @return array
     */
    public function getUser($openId, $accessToken)
    {
        $queries = array(
            'access_token' => $accessToken,
            'openid'       => $openId,
            'lang'         => 'zh_CN',
        );
        return $this->parseJSON(Http::get(self::API_USER,$queries));
    }

    /**
     * 获取access token
     *
     * @param string $code
     *
     * @return string
     */
    public function getAccessPermission($code)
    {
        $params = array(
            'appid'      => $this->appId,
            'secret'     => $this->appSecret,
            'code'       => $code,
            'grant_type' => 'authorization_code',
        );
        $res=$this->parseJSON(Http::post(self::API_TOKEN_GET.'?'.http_build_query($params)));
        if(isset($res['errorcode'])){
            trigger_error('get access_token error: '.$res['errormsg'], E_USER_ERROR);
        }
        return $this->lastPermission = $res;
    }
}