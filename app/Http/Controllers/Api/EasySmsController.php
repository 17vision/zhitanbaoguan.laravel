<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Overtrue\EasySms\EasySms;
use App\Rules\Phone;
use Exception;

class EasySmsController extends Controller
{
    public function sendSms(Request $request)
    {
        $request->validate([
            'phone' => ['required',new Phone()],
        ], [], [
            'phone' => '手机号码',
        ]);

        $phone = $request->phone;

        if (! app()->environment('production')) {
            $code = '1024';
        } else {
            // 生成4位随机数，左侧补0
            $code = str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);
            try {
                $easySms = new EasySms(config('easysms'));
                $result = $easySms->send($phone, [
                    'template' => config('easysms.gateways.aliyun.templates.register'),
                    'data' => [
                        'code' => $code
                    ],
                ]);

                Log::channel('easysms')->info('easysms', ['code' => $code, 'phone' => $phone, 'result' => $result]);
            } catch (Exception $e) {
                Log::channel('easysms')->error('easysms', ['message' => $e->getMessage(), 'line' => $e->getLine(), 'FILE' => $e->getFile()]);

                return response()->json(['message' => '短信发送异常'], 403);
            }
        }

        $key = 'sms_' . $phone . '_' . Str::random(4);
        $expiredAt = now()->addMinutes(15);
        // 缓存验证码 15 分钟过期。
        Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);
        return response()->json(['key' => $key, 'expired_at' => $expiredAt->toDateTimeString()]);
    }
}
