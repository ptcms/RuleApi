<?php

namespace Kuxin\Weixin;

use Kuxin\Helper\Http;

class Resource extends Weixin{

    const API_MEDIA_GET="https://api.weixin.qq.com/cgi-bin/media/get";

    public function getMedia($media_id)
    {
        $params=[
            'access_token'=>$this->getToken(),
            'media_id'=>$media_id,
        ];
        return Http::get(self::API_MEDIA_GET,http_build_query($params));
    }
}