<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Models\Role;
use App\Rules\Phone;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'phone' => [
                'filled',
                new Phone()
            ],
            'role_id' => 'filled|integer',
            'limit' => 'filled|integer'
        ], [], [
            'phone' => '手机号码',
            'role_id' => '角色 id',
            'limit' => '单页条数'
        ]);

        $limit = $request->input('limit', 30);

        $phone = $request->phone;

        $role_id = $request->role_id;

        $query = User::query();

        if ($phone) {
            $query->where('phone', $phone);
        }

        if ($role_id) {
            $query->whereHas('roles', function ($query) use ($role_id) {
                return $query->where('id', $role_id);
            });
        }

        $users = $query->with(['userExtend:user_id', 'roles'])->orderBy('id', 'desc')
            ->select(['id', 'viewid', 'nickname', 'phone', 'gender', 'avatar', 'email', 'qq', 'wechat', 'birthday', 'referer', 'register_ip', 'signature', 'created_at'])
            ->paginate($limit);

        foreach ($users as &$user) {
            if ($user['phone']) {
                $user['phone'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $user['phone']);
            }

            foreach ($user['roles'] as $key => $role) {
                $user['roles'][$key] = $role->only(['id', 'name', 'title']);
            }
        }

        // 追加角色信息
        $roles = Role::get()->all();
        foreach ($roles as &$role) {
            $role = $role->only(['id', 'title']);
        }
        $roles = collect(['roles' => $roles]);
        $users = $roles->merge($users);

        return response()->json($users);
    }

    public function detail(Request $request)
    {
    }

    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'nickname' => 'filled|string',
            'password' => 'filled|string'
        ], [], [
            'user_id' => '用户 id',
            'nickname' => '昵称',
            'password' => '密码'
        ]);

        $data = $request->only(['nickname', 'password']);

        if (empty($data)) {
            return response()->json(['message' => '更新对象不能为空'], 403);
        }

        $user_id = $request->user_id;

        $user = User::where('id', $user_id)->first();
        if (!$user) {
            return response()->json(['message' => '用户不存在'], 403);
        }

        if (isset($data['password'])) {
            if (mb_strlen($data['password']) < 6) {
                return response()->json(['message' => '密码长度不得小于 6 位'], 403);
            }

            if (mb_strlen($data['password']) > 12) {
                return response()->json(['message' => '密码长度不得大于 12 位'], 403);
            }
            $data['password'] = Hash::make($data['password']);
        }

        $result = $user->update($data);

        // 更新完密码后,清除用户的 token
        if ($result && isset($data['password'])) {
            $user->tokens()->delete();
        }

        return response()->json($result);
    }

    public function getUserRoles(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer'
        ], [], [
            'user_id' => '用户 id'
        ]);

        $user_id = $request->user_id;

        $user = User::where('id', $user_id)->first();
        if (!$user) {
            return response()->json(['message' => '用户不存在'], 403);
        }

        $ids = $user->roles->pluck('id')->all();

        $roles = Role::get()->all();

        return response()->json(['nickname' => $user->nickname, 'viewid' => $user['viewid'], 'ids' => $ids, 'roles' => $roles]);
    }

    public function setUserRoles(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'ids' => 'nullable|array'
        ], [], [
            'user_id' => '用户 id',
            'ids' => '权限 id'
        ]);

        $user_id = $request->user_id;

        $ids = $request->ids;

        $user = User::where('id', $user_id)->first();
        if (!$user) {
            return response()->json(['message' => '用户不存在'], 403);
        }

        $result = $user->syncRoles($ids);

        return response()->json($result);
    }

    // 导入用户
    public function importUser(Request $request)
    {
        $lists = Excel::toArray(null, request()->file('file'));
    }
}
