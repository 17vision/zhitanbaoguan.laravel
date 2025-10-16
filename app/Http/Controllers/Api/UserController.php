<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\Authorization;

class UserController extends Controller
{
    use Authorization;

    const WXMINI_SESSION_KEY = 'wxmini_session_key';

    public function wxminiLogin(Request $request)
    {
        $request->validate([
            'code' => 'required_without:openid|string',
            'openid' => 'required_without:code|string',
            'nickname' => 'required_without:code|string',
            'avatar' => 'required_without:code|string',
        ], [], [
            'code' => 'code',
            'openid' => 'openid',
            'nickname' => '昵称',
            'avatar' => '头像'
        ]);

        $code = $request->code;

        if ($code) {
            $appid = config('auth.wxmini.appid');
            $secret = config('auth.wxmini.secret');

            $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";

            $result = curl($url, true, '');

            $result = json_decode($result, true);

            if (isset($result['errcode'])) {
                return response()->json(['errors' => ['message' => $result['errcode']]])->setStatusCode(403);
            }

            if (!isset($result['openid'])) {
                return response()->json(['errors' => ['message' => '缺少 openid']])->setStatusCode(403);
            }

            $session_key = $result['session_key'];

            $user = User::where('wxmini_openid', $result['openid'])->first();

            if (!$user) {
                $key = self::WXMINI_SESSION_KEY . '_' . $result['openid'];

                $expiredAt = now()->addMinutes(5);

                Cache::put($key, $session_key, $expiredAt);

                unset($result['session_key']);

                return response()->json($result);
            }

            $user->update(['wxmini_session_key' => $session_key]);
        } else {
            $openid = $request->openid;
            $nickname = $request->nickname;
            $avatar = $request->avatar;

            $key = self::WXMINI_SESSION_KEY . '_' . $openid;
            $session_key = Cache::get($key);
            if (!$session_key) {
                return response()->json(['message' => '请重新操作'], 403);
            }
            Cache::forget($key);

            $data = [
                'wxmini_openid' => $openid,
                'nickname' => $nickname,
                'avatar' => reverseStorageUrl($avatar),
                'wxmini_session_key' => $session_key,
                'register_ip' => $request->getClientIp()
            ];

            $user = User::create($data);
        }
        return response()->json($this->auth($user['id']));
    }
}
