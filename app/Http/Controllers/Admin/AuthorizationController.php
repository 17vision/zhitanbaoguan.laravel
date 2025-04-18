<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\UserExtend;
use App\Models\Role;
use App\Rules\Phone;
use App\Traits\BuildWxcode;

class AuthorizationController extends Controller
{
    use BuildWxcode;

    public function login(Request $request)
    {
        $request->validate([
            'account' => ['required_without:email', new Phone()],
            'email' => ['required_without:account', 'email'],
            'password' => 'required|min:6|max:16',
        ], [

        ], [
            'account' => '账号',
            'email' => '账号',
            'password' => '密码',
        ]);

        $data = $request->only(['account', 'email', 'password']);

        if (Auth::validate($data)) {
            if (isset($data['account'])) {
                $user = User::where('account', $data['account'])->first();
            } else {
                $user = User::where('email', $data['email'])->first();
            }

            $token = $user->createToken('auth')->plainTextToken;

            UserExtend::where('id', $user->id)->update(['admin_lock' => null]);

            return response()->json(['token' => $token]);
        } else {
            return response()->json(['message' => '账号或密码错误'], 403);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'account' => ['required_without:email', new Phone()],
            'email' => ['required_without:account', 'email'],
            'password' => 'required|min:6|max:16',
            'captcha_code' => 'required|string',
            'captcha_key' => 'required|string',
        ], [

        ], [
            'account' => '账号',
            'email' => '账号',
            'password' => '密码',
            'captcha_code' => '验证码',
            'captcha_key' => '验证码',
        ]);

        $data = $request->only(['account', 'email', 'password']);

        $captchaData = Cache::get('captcha_' . $request->captcha_key);
        if (!$captchaData) {
            return response()->json(['message' => '验证码已失效'], 403);
        }

        if (!hash_equals($captchaData['code'], strtolower($request->captcha_code))) {
            // 验证错误就清除缓存
            // Cache::forget('captcha_' . $request->captcha_key);
            return response()->json(['message' => '验证码错误'], 403);
        }

        Cache::forget('captcha_' . $request->captcha_key);

        $data['password'] = Hash::make($data['password']);

        if (isset($data['account'])) {
            $data['phone'] = $data['account'];

            if (User::where('account', $data['account'])->exists()) {
                return response()->json(['message' => '该手机号码已注册'], 403);
            }
        } else {
            if (User::where('email', $data['email'])->exists()) {
                return response()->json(['message' => '该邮箱已注册'], 403);
            }
        }

        $data['register_ip'] = $request->getClientIP();

        $data['referer'] = 8;

        $user = User::create($data);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function getUserInfo(Request $request)
    {
        $user = $request->user();

        if (!empty($user['roles']->all())) {
            $admin_role = UserExtend::where('id', $user['id'])->value('admin_role');

            if ($admin_role) {
                if (!$user->hasRole($admin_role)) {
                    $admin_role = $user['roles'][0]['name'];

                    UserExtend::where('id', $user['id'])->update(['admin_role' => $admin_role]);
                }
            } else {
                $admin_role = $user['roles'][0]['name'];

                UserExtend::where('id', $user['id'])->update(['admin_role' => $admin_role]);
            }
        }

        if (!isset($admin_role) || !$admin_role) {
            return response()->json(['message' => '您好，你没有后台使用权限'], 403);
        }

        $user = $user->only(['id', 'viewid', 'nickname', 'phone', 'gender', 'avatar', 'email', 'qq', 'wechat', 'birthday', 'signature', 'roles']);

        $permissions = Role::findByName($admin_role)->permissions()->get()->all();

        $user['menus'] = $this->getMenu($permissions);

        $user['curRole'] = $admin_role;

        $user['adminLock'] = UserExtend::where('id', $user['id'])->value('admin_lock') ? true : false;

        return response()->json($user);
    }


    // 获取用户的菜单
    public function getMenu($permissions, $parent_id = 0)
    {
        $array = [];
        foreach ($permissions as $key => $permission) {
            if ($permission['parent_id'] === $parent_id) {
                $permission['children'] = $this->getMenu($permissions, $permission['id']);
                $array[] = $permission;
            }
        }

        // 对权限进行排序
        $orders = array_column($array, 'order');

        array_multisort($orders, SORT_ASC, $array);

        return $array;
    }

    public function setRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string'
        ], [], [
            'role' => '角色'
        ]);

        $user = $request->user();

        $role = $request->role;

        if (!$user->hasRole($role)) {
            return response()->json(['error' => '客观不可用']);
        }

        UserExtend::where('id', $user->id)->update(['admin_role' => $role]);

        return response()->json(['message' => '设置角色完成']);
    }

    public function setPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:6|max:16',
            'captcha_code' => 'required|string',
            'captcha_key' => 'required|string',
        ], [

        ], [
            'password' => '密码',
            'captcha_code' => '验证码',
            'captcha_key' => '验证码',
        ]);

        $password = $request->password;

        $captchaData = Cache::get('captcha_' . $request->captcha_key);
        if (!$captchaData) {
            return response()->json(['message' => '验证码已失效'], 403);
        }

        if (!hash_equals($captchaData['code'], strtolower($request->captcha_code))) {
            // 验证错误就清除缓存
            // Cache::forget('captcha_' . $request->captcha_key);
            return response()->json(['message' => '验证码错误'], 403);
        }
        Cache::forget('captcha_' . $request->captcha_key);

        $password = Hash::make($password);

        $result = ($request->user())->update(['password' => $password]);

        return response()->json($result);
    }

    public function getUserProfile(Request $request)
    {
        $user = $request->user()->only(['id', 'viewid', 'avatar', 'nickname', 'gender', 'phone', 'email', 'wechat', 'signature']);

        return response()->json($user);
    }

    public function setUserProfile(Request $request)
    {
        $this->validate($request, [
            'nickname' => 'required|string',
            'gender' => 'required|in:1,2',
            'email' => 'nullable|email',
            'wechat' => 'nullable|string',
            'signature' => 'nullable|string',
        ], [], [
            'nickname' => '昵称',
            'gender' => '性别',
            'email' => '邮箱',
            'wechat' => '微信',
            'signature' => '签名',
        ]);

        $data = $request->only(['nickname', 'gender', 'email', 'wechat', 'signature']);

        $user = $request->user();

        $result = $user->update($data);

        return response()->json($result);
    }

    // 设置锁屏密码
    public function setLock(Request $request)
    {
        $request->validate([
            'password' => 'required|min:4|max:8'
        ],[],[
            'password' => '密码'
        ]);

        $password = $request->password;

        $user = $request->user();

        $result = UserExtend::query()->where('user_id', $user->id)->update(['admin_lock' => Hash::make($password)]);

        return response()->json($result);
    }

    // 检查锁屏密码
    public function checkLock(Request $request)
    {
        $request->validate([
            'password' => 'required'
        ],[],[
            'password' => '密码'
        ]);

        $password = $request->password;

        $user = $request->user();

        $admin_lock = UserExtend::query()->where('user_id', $user->id)->value('admin_lock');

        if (!$admin_lock) {
            $result = true;

            return response()->json(['message' => '没有上锁']);
        } else {
            $result =  Hash::check($password, $admin_lock);

            if ($result) {
                UserExtend::query()->where('user_id', $user->id)->update(['admin_lock' => null]);

                return response()->json(['message' => '密码正确']);
            }

            return response()->json(['message' => '密码错误'], 403);
        }
    }

    // 生成小程序码
    public function getQrcode()
    {
        $key = 'ad' . session_create_id();

        $time = 120;

        Redis::setex($key, $time, null);

        $data = [
            'scene' => 'key=' . $key,
            'page' => 'pages/mine/part/admin-login',
            'width' => 300
        ];

        $image = $this->buildWxcode($data);

        return response()->json(['path' => $image, 'key' => $key, 'ttl' => $time]);
    }

    // 轮询来获取 token
    public function getToken(Request $request)
    {
        $request->validate([
            'key' => 'required|string'
        ],[],[
            'key' => 'key'
        ]);

        $key = $request->key;

        $token = Redis::get($key);

        return response()->json(['token' => $token]);
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'key' => 'required|string'
        ],[],[
            'key' => 'key'
        ]);

        $data = $request->only(['nickname', 'phone', 'gender', 'avatar', 'email']);

        $key = $request->key;

        if (isset($data['phone'])) {
            $user = User::where('phone', $data['phone'])->first();
        } elseif(isset($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        }

        if (!$user) {
            $data['register_ip'] = $request->getClientIP();

            $data['referer'] = 9;

            if (isset($data['avatar']) && strpos($data['avatar'], 'yuepaibao')) {
                unset($data['avatar']);
            }

            $user = User::create($data);
        }

        if (!Redis::exists($key)) {
            return response()->json(['message' => '小程序码已过期'], 403);
        }

        $token = $user->createToken('auth')->plainTextToken;

        Redis::setex($key, 10, $token);

        return response()->json(['message' => '已授权']);
    }
}
