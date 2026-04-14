<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use App\Traits\Authorization;
use Carbon\Carbon;

class UserController extends Controller
{
    use Authorization;

    const WXMINI_SESSION_KEY = 'wxmini_session_key';

    public function getUserInfo(Request $request)
    {
        $user = $request->user();

        return response()->json($this->auth($user['id'], false));
    }

    public function wxminiLogin(Request $request)
    {
        $request->validate([
            'code' => 'required_without:openid|string',
            'openid' => 'required_without:code|string',
            'nickname' => 'required_without:code|string',
        ], [], [
            'code' => 'code',
            'openid' => 'openid',
            'nickname' => '昵称',
        ]);

        $code = $request->code;

        if ($code) {
            $appid = config('auth.wxmini.appid');
            $secret = config('auth.wxmini.secret');

            $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";

            $result = curl($url, true, false, true);

            $result = json_decode($result, true);

            if (isset($result['errcode'])) {
                return response()->json(['errors' => ['message' => $result['errcode']]])->setStatusCode(403);
            }

            if (!isset($result['unionid'])) {
                return response()->json(['errors' => ['message' => '缺少 unionid']])->setStatusCode(403);
            }

            $session_key = $result['session_key'];

            $user = User::where('wx_unionid', $result['unionid'])->orWhere('wxmini_openid', $result['openid'])->first();

            if (!$user) {
                $key = self::WXMINI_SESSION_KEY . '_' . $result['openid'];

                $cacheData = [
                    'session_key' => $session_key,
                    'openid' => $result['openid'],
                    'unionid' => $result['unionid'] ?? ''
                ];

                $expiredAt = now()->addMinutes(5);

                Cache::put($key, $cacheData, $expiredAt);

                unset($result['session_key']);

                return response()->json($result);
            }

            $data = ['wxmini_session_key' => $session_key];

            if (!$user['wx_unionid'] && isset($result['unionid']) && $result['unionid']) {
                $data['wx_unionid'] = $result['unionid'];
            }

            $user->update($data);
        } else {
            $openid = $request->openid;
            $nickname = $request->nickname;

            $key = self::WXMINI_SESSION_KEY . '_' . $openid;
            $cacheData = Cache::get($key);
            if (!$cacheData || !$cacheData['session_key'] || !$cacheData['openid'] || !$cacheData['unionid']) {
                return response()->json(['message' => '请重新操作'], 403);
            }
            Cache::forget($key);

            $data = [
                'wxmini_openid' => $openid,
                'nickname' => $nickname,
                'wxmini_session_key' => $cacheData['session_key'],
                'wx_unionid' => $cacheData['unionid'],
                'register_ip' => $request->getClientIp()
            ];

            $user = User::create($data);
        }
        return response()->json($this->auth($user['id']));
    }

    // 微信 app 登录
    public function wxappLogin(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ], [], [
            'code' => 'code'
        ]);

        $code = $request->input('code');

        $appid = config('auth.wxopen.client_id');

        $secert = config('auth.wxopen.client_secret');

        if (!$appid || !$secert) {
            return response()->json(['message' => '缺少配置，请联系管理员'], 403);
        }

        Log::info('wxapp-login-0', ['appid' => $appid, 'secert' => $secert, 'code' => $code]);

        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secert&code=$code&grant_type=authorization_code";

        $result = curl($url, false, false, true);

        $result = json_decode($result, true);

        Log::info('wxapp-login-1', $result);

        if (isset($result['errcode']) && $result['errcode']) {
            return response()->json(['message' => $result['errcode']])->setStatusCode(403);
        }

        $openid = $result['openid'];

        $unionid = $result['unionid'];

        $access_token = $result['access_token'];

        $user = User::query()->where('wx_unionid', $unionid)->first();

        if ($user) {
            if (!$user['wxapp_openid']) {
                $user->update(['wxapp_openid' => $openid]);
            }
            return response()->json($this->auth($user['id']));
        }

        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid";

        $result = curl($url, false, false, true);

        $result = json_decode($result, true);

        Log::info('wxapp-login-2', $result);

        if (isset($result['errcode']) && $result['errcode']) {
            return response()->json(['message' => $result['errcode']])->setStatusCode(403);
        }

        $data = [
            'wxapp_openid' => $openid,
            'wx_unionid' => $unionid,
            'nickname' => $result['nickname'],
            'avatar' => $result['headimgurl']
        ];

        if ($result['sex']) {
            $data['gender'] = $result['sex'];
        }

        $user = User::create($data);

        return response()->json($this->auth($user['id']));
    }

    public function update(Request $request)
    {
        $request->validate([
            'nickname' => 'filled|string',
            'gender' => 'filled|in:1,2',
            'avatar' => 'filled|string',
            'signature' => 'filled|string',
        ], [], [
            'nickname' => '昵称',
            'gender' => '性别',
            'avatar' => '头像',
            'signature' => '签名',
        ]);

        $data = $request->only(['nickname', 'gender', 'avatar', 'signature']);

        $user = $request->user();

        if (empty($data)) {
            return response()->json(['message' => '请提交数据'], 403);
        }

        if (isset($data['avatar'])) {
            $data['avatar'] = reverseStorageUrl($data['avatar']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function logins(Request $request)
    {
        $request->validate([
            'year' => 'filled|integer|min:2024|max:2030',
            'month' => 'required_with:year|integer|min:1|max:12'
        ], [], [
            'year' => '年',
            'month' => '月'
        ]);

        $user = $request->user();

        $date = $request->filled('year') ? Carbon::create($request->input('year'), $request->input('month'), 1) : Carbon::now();

        $start = $date->copy()->startOfMonth()->startOfDay();
        $end   = $date->copy()->endOfMonth()->endOfDay();

        $loginDates = UserLogin::query()->where('user_id', $user->id)->whereBetween('login_date', [$start, $end])->orderBy('login_date', 'asc')->pluck('login_date')->toArray();

        $days = [];
        $today = Carbon::now()->startOfDay();

        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $dayStr = $d->toDateString();
            $days[] = [
                'date'  => $dayStr,
                'has_data' => \in_array($dayStr, $loginDates),
                'is_future'  => $d->gt($today),
            ];
        }

        return response()->json($days);
    }
}
