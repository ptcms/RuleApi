<?php

namespace Kuxin\Alipay;

use Kuxin\Config;
use Kuxin\Helper\Http;
use Kuxin\Helper\Xml;

class Pay extends Alipay
{
    
    const PC_PAY_URL = 'https://mapi.alipay.com/gateway.do';
    
    const WAP_PAY_URL = 'http://wappaygw.alipay.com/service/rest.htm';
    
    public function getPcUrl($data)
    {
        $data         = array_merge([
            'service'        => 'create_direct_pay_by_user',
            '_input_charset' => 'utf-8',
            'partner'        => $this->appId,
            'format'         => 'xml',
            'v'              => '2.0',
            'sign_type'      => 'MD5',
            'notify_url'     => '',
            'return_url'     => '',
            'out_trade_no'   => '',
            'subject'        => '',
            'body'           => '',
            'payment_type'   => '1',
            'total_fee'      => '',
            'seller_id'      => $this->appId,
            'seller_email'   => Config::get('alipay.email'),
            'show_url'       => '',
        ], $data);
        $data['sign'] = $this->sign($data);
        return self::PC_PAY_URL . '?' . http_build_query($data);
    }
    
    public function getWapUrl($param)
    {
        $req_data = '<direct_trade_create_req>';
        $req_data .= '<notify_url>' . $param['notify_url'] . '</notify_url>';
        $req_data .= '<call_back_url>' . $param['return_url'] . '</call_back_url>';
        $req_data .= '<seller_account_name>' . Config::get('pay_alipay_email') . '</seller_account_name>';
        $req_data .= '<out_trade_no>' . $param['out_trade_no'] . '</out_trade_no>';
        $req_data .= '<subject>' . $param['subject'] . '</subject>';
        $req_data .= '<total_fee>' . $param['total_fee'] . '</total_fee>';
        $req_data .= '</direct_trade_create_req>';
        
        $data         = [
            'service'        => 'alipay.wap.trade.create.direct',
            '_input_charset' => 'utf-8',
            'partner'        => $this->appId,
            'sec_id'         => 'MD5',
            'req_id'         => date('Ymdhis'),
            'req_data'       => $req_data,
            'v'              => '2.0',
            'format'         => 'xml',
        ];
        $data['sign'] = $this->sign($data);
        $res          = urldecode(Http::post(self::WAP_PAY_URL, $data));
        parse_str($res, $param);
        if (!empty($param['res_data'])) {
            $data = Xml::decode($param['res_data']);
            //业务详细
            $req_data     = '<auth_and_execute_req><request_token>' . $data['request_token'] . '</request_token></auth_and_execute_req>';
            $data         = [
                'service'        => 'alipay.wap.auth.authAndExecute',
                '_input_charset' => 'utf-8',
                'partner'        => $this->appId,
                'sec_id'         => 'MD5',
                'req_id'         => date('Ymdhis'),
                'req_data'       => $req_data,
                'v'              => '2.0',
                'format'         => 'xml',
            ];
            $data['sign'] = $this->sign($data);
            return self::WAP_PAY_URL . '?' . http_build_query($data);
        }
        return false;
    }
    
    
    public function notify($param)
    {
        unset($param['m'], $param['c'], $param['a'], $param['s'], $param['f']);
        $sign = $this->sign($param);
        if ($sign == $param['sign']) {
            if ($param['trade_status'] == 'TRADE_FINISHED' || $param['trade_status'] == 'TRADE_SUCCESS') {
                return ['status' => 1, 'info' => 'success', 'money' => $param['total_fee'], 'time' => strtotime(isset($param['gmt_payment'])) ? $param['gmt_payment'] : $param['notify_time']];
            } else {
                return ['status' => 0, 'info' => 'success'];
            }
        } else {
            return ['status' => 0, 'info' => 'fail'];
        }
    }
}