<?php

namespace Kuxin\Weixin;

use Kuxin\Config;
use Kuxin\Helper\Http;
use Kuxin\Helper\Json;
use Kuxin\Helper\Xml;
use Kuxin\Request;

class Pay extends Weixin
{
    
    const API_UNIFIED_ORDER = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    
    public function unifiedorder($data)
    {
        $data         = array_merge([
            'appid'            => $this->appId,
            'mch_id'           => Config::get('weixin.mchid'),
            'nonce_str'        => $this->getNonceStr(),
            'notify_url'       => Config::get('weixin.notifyurl'),
            'spbill_create_ip' => Request::getIp(),
            'trade_type'       => 'JSAPI',
        ], $data);
        $data['sign'] = $this->makeSign($data);
        
        $xml = Xml::encode($data);
        $res = Http::post(self::API_UNIFIED_ORDER, $xml);
        if($res){
            $res = Xml::decode($res);
            if ($res['return_code'] == 'SUCCESS') {
                return $res['prepay_id'];
            } else {
                return $res['return_msg'];
            }
        }
        
    }
    
    public function notify($data = null)
    {
        if (!$data) {
            $data = file_get_contents('php://input');
        }
        $data = Xml::decode($data);
        if (isset($data['return_code']) && $data['return_code'] == 'SUCCESS') {
            if ($data['sign'] == $this->makeSign($data)) {
                return [
                    'id'             => $data['out_trade_no'],
                    'is_subscribe'   => $data['is_subscribe'],
                    'openid'         => $data['openid'],
                    'attach'         => isset($data['attach']) ? $data['attach'] : "",
                    'time_end'       => strtotime($data['time_end']),
                    'transaction_id' => $data['transaction_id'],
                    'money'          => $data['total_fee'] / 100,
                ];
            } else {
                return '签名验证失败';
            }
        } else {
            return '解析数据失败';
        }
    }
    
    public function jsPayConfig($prepay_id, $json = true)
    {
        $data            = [
            'appId'     => $this->appId,
            'timeStamp' => (string)$_SERVER['REQUEST_TIME'],
            'nonceStr'  => $this->getNonceStr(),
            'package'   => 'prepay_id=' . $prepay_id,
            'signType'  => 'MD5',
        ];
        $data['paySign'] = $this->makeSign($data);
        return $json ? Json::encode($data) : $data;
    }
    
    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams($data)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        
        $buff = trim($buff, "&");
        return $buff;
    }
    
    /**
     * 生成签名
     *
     * @return string 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($data)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->toUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . Config::get('weixin.paykey');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    
}