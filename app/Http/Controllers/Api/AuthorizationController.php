<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use App\Traits\Authorization;
use App\Models\User;
use App\Rules\Phone;

class AuthorizationController extends Controller
{
    use Authorization;

    // 通过账号登录
    public function passwordLogin(Request $request)
    {
        $this->validate($request, [
            'phone' => ['required',new Phone()],
            'password' => 'required|string|max:16|min:6',
        ], [], [
            'phone' => '手机',
            'password' => '密码',
        ]);

        $data = [
            'account' => $request->phone,
            'password' => $request->password
        ];

        if (Auth::attempt($data, false)) {
            $user = auth()->user();

            return response()->json($this->auth($user['id']));
        } else {
            return response()->json(['message' => '账号或密码错误'], 403);
        }
    }

    // 通过密码注册
    public function passwordRegister(Request $request)
    {
        $this->validate($request, [
            'phone' => ['required',new Phone()],
            'password' => 'required|string|max:16|min:6',
            'code' => 'required|string',
            'key' => 'required|string'
        ], [], [
            'phone' => '手机',
            'password' => '密码',
            'code' => '验证码',
            'password' => 'key'
        ]);

        $phone = $request->phone;
        $password = $request->password;
        $code = $request->code;
        $key = $request->key;

        // 先验证验证码
        $cacheData = Cache::get($key);
        if (!$cacheData) {
            return response()->json(['message' => '验证码已过期'], 403);
        }

        if ($cacheData['code'] != $code) {
            return response()->json(['message' => '验证码错误'], 403);
        }

        if ($cacheData['phone'] != $phone) {
            return response()->json(['message' => '手机不匹配'], 403);
        }

        if ($cacheData['code'] != $code || $cacheData['phone'] != $phone) {
            return response()->json(['message' => '验证码错误'], 403);
        }
        Cache::forget($key);

        // 再处理用户
        $user = User::where('phone', $phone)->first();

        if ($user) {
            $data = [
                'account' => $phone,
                'password' => Hash::make($password),
            ];

            $user->update();
        } else {
            $data = [
                'phone' => $phone,
                'account' => $phone,
                'password' => Hash::make($password),
                'referer' => 4,
                'register_ip' => $request->getClientIP()
            ];

            $user = User::create($data);
        }
        return response()->json($this->auth($user['id']));
    }

    // 授权后台登录 (授权，只管生成 token，并放在 redis 中)
    public function adminLogin(Request $request)
    {
        $request->validate([
            'key' => 'required|string'
        ],[],[
            'key' => 'key'
        ]);

        $key = $request->key;
        if (!Redis::exists($key)) {
            return response()->json(['message' => '小程序码已过期'], 403);
        }

        $user = User::where('id', $request->user()->id)->first();

        $token = $user->createToken('auth')->plainTextToken;

        Redis::setex($key, 10, $token);

        return response()->json(['message' => '已授权']);
    }
}
