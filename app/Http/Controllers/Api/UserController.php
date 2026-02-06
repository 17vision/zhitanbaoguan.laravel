<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\UserBodyMetric;
use App\Models\UserDailyStep;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use App\Traits\Authorization;
use Carbon\Carbon;
use Illuminate\Support\Arr;

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

    public function getUserDailySteps(Request $request)
    {
        $request->validate([
            'type' => 'required_without:date|in:day,week,month',
            'date' => 'filled|date'
        ], [], [
            'type' => '类型',
            'date' => '日'
        ]);

        $date = $request->input('date');
        $type = $request->input('type');
        $user = $request->user();

        if ($date || $type == 'day') {
            $date = $date ?? Carbon::now()->toDateString();
            $userDailySteps = UserDailyStep::query()->where('user_id', $user->id)->where('date', $date)->get()->toArray();

            $userDailySteps = Arr::keyBy($userDailySteps, 'hour');

            $start = Carbon::parse($date)->startOfDay();
            $end = $start->copy()->endOfDay();
            for ($i = $start; $i->lt($end); $start->addHour()) {
                $hour = $i->hour;
                if (isset($userDailySteps[$hour])) {
                    $userDailySteps[$hour] = self::transDailyStep($userDailySteps[$hour]);
                } else {
                    $userDailySteps[$hour] = self::transDailyStep(null, $user->id, $date, $hour);
                }
            }

            sort($userDailySteps);

            return response()->json($userDailySteps);
        }

        $end = Carbon::now();

        $start = $type == 'week' ? $end->copy()->addDays(-6) : $end->copy()->addDays(-29);

        $userDailySteps = UserDailyStep::query()->where('user_id', $user->id)
                                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                                ->groupBy(['user_id', 'date'])
                                ->selectRaw('user_id,date,SUM(steps) AS steps,SUM(calories) AS calories,SUM(distance) AS distance')
                                ->get()
                                ->toArray();

        $userDailySteps = Arr::keyBy($userDailySteps, 'date');

        $results = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dateStr = $d->toDateString();
            if (isset($userDailySteps[$dateStr])) {
                $results[] = self::transDailyStep($userDailySteps[$dateStr]);
            } else {
                $results[] = self::transDailyStep(null, $user->id, $dateStr, null);
            }
        }
        return response()->json($results);
    }

    private static function transDailyStep($item = null, $user_id = null, $date = null, $hour = null)
    {
        if ($item) {
            return [
                'user_id' => $item['user_id'],
                'date' => $item['date'],
                'hour' => $item['hour'] ?? null,
                'steps' => $item['steps'],
                'calories' => $item['calories'],
                'distance' => $item['distance'],
            ];
        }

        return [
            'user_id' => $user_id,
            'date' => $date,
            'hour' => $hour,
            'steps' => 0,
            'calories' => 0,
            'distance' => 0,
        ];
    }
}
