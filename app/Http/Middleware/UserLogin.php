<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserLogin as UserLoginModel;
use Exception;

class UserLogin
{
    // 用来记录用户登录的特征，比如 ip地址，经纬度等信息（ 6 个 小时的生命周期）
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('api')->user();

        // 6 小时间隔统计登录(这里做成异步更好)
        if (isset($user) && $user) {
            $key = 'user_login_' . $user->id;
            $time = 6 * 60 * 60;
            $res = Redis::setnx($key, $time);
            if ($res) {
                Redis::expire($key, $time);

                $login_at = Carbon::now()->toDateTimeString();

                $ip = $request->getClientIP();

                try {
                    $data = getCityByIp($ip);

                    if ($request->system) {
                        $data['system'] = $request->system;
                    }

                    if ($request->device_model) {
                        $data['device_model'] = $request->device_model;
                    }

                    if ($request->device_type) {
                        $data['device_type'] = $request->device_type;
                    }

                    if ($request->os_name) {
                        $data['os_name'] = $request->os_name;
                    }

                    if (!empty($data)) {
                        $data['user_id'] = $user->id;
                        $data['ip'] = $ip;
                        $data['login_at'] = $login_at;
                        UserLoginModel::create($data);

                        if ($data['city']) {
                            User::where('id', $user->id)->update(['territory_ip' => $data['city'], 'last_login_at' => $login_at]);
                        } else {
                            User::where('id', $user->id)->update(['last_login_at' => $login_at]);
                        }
                    }
                } catch (Exception $e) {

                }
            }
        }

        return $next($request);
    }
}
