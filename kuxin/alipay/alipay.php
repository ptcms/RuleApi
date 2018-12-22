<?php
namespace Kuxin\Alipay;
use Kuxin\Config;
use Kuxin\Loader;

class Alipay{
    
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
    
    public function __construct($appId, $appSecret)
    {
        $this->appId     = $appId ?: Config::get('alipay.appid');
        $this->appSecret = $appSecret ?: Config::get('alipay.appsecret');
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
    
    public function sign($data) {
        ksort($data);
        reset($data);
        $arg = '';
        foreach ($data as $key => $value) {
            if ($key != 'sign_type' && $key != 'sign' && $value!='') {
                $arg .= "$key=$value&";
            }
        }
        return md5(substr($arg, 0, -1) . $this->appSecret);
    }
}