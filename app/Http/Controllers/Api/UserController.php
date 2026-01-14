<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\UserBodyMetric;
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

            $key = self::WXMINI_SESSION_KEY . '_' . $openid;
            $session_key = Cache::get($key);
            if (!$session_key) {
                return response()->json(['message' => '请重新操作'], 403);
            }
            Cache::forget($key);

            $data = [
                'wxmini_openid' => $openid,
                'nickname' => $nickname,
                'wxmini_session_key' => $session_key,
                'register_ip' => $request->getClientIp()
            ];

            $user = User::create($data);
        }
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

    public function getUserBodyMetrics(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer|min:1',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示多少',
        ]);

        $limit = $request->input('limit', 20);

        $user = $request->user();

        $user_body_metrics = UserBodyMetric::query()->where('user_id', $user->id)->orderByDesc('id')->simplePaginate($limit);

        return response()->json($user_body_metrics);
    }
}
