<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;

trait BuildWxcode {

    protected function buildWxcode($data)
    {
        $appid = config('auth.wxmini.appid');

        $secret = config('auth.wxmini.secret');

        $access_token = getWxMiniAccessToken($appid, $secret);

        if (!$access_token) {
            return false;
        }

        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;

        $result = curl($url, json_encode($data), true, true);

        try {
            $info = json_decode($result, true);
            if ($info && $info['errcode']) {
                Log::channel('error')->error('生成小程序码失败', $info);

                if ($info['errcode'] == 40001) {
                    cleanWxMiniAccessToken($appid);
                }

                return false;
            }
        } catch (Exception $e) {
           return false;
        }

        $image = "data:image/jpeg;base64," . base64_encode($result);

        return $image;
    }
}
