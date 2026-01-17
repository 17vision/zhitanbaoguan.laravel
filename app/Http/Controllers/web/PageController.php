<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\GradeUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Exception;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function getUser()
    {
        return view('activity.user');
    }
    public function storeUser(Request  $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:50'],
            'gender'   => ['required', 'in:1,2'],
            'age'      => ['required', 'integer', 'between:1,150'],
            'phone'    => ['required', 'regex:/^1[3-9]\d{9}$/'],
        ], [
            'username.required' => '姓名不能为空',
            'gender.required'   => '请选择性别',
            'age.required'      => '年龄不能为空',
            'phone.required'    => '手机号码不能为空',
            'phone.regex'       => '请输入有效的中国大陆手机号码',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->only(['username', 'gender', 'age', 'phone']);

        // 八院活动的
        $grade_id = 5;

        $user = User::query()->where('phone', $data['phone'])->orWhere('account', $data['phone'])->first();

        DB::beginTransaction();
        try {
            if ($user) {
                $user->update($data);
            } else {
                $data['account'] = $data['phone'];
                $data['register_ip'] = $request->ip();
                $data['password'] = Hash::make('123456');
                $user = User::create($data);
            }

            if (!GradeUser::query()->where('grade_id', $grade_id)->where('user_id', $user['id'])->exists()) {
                GradeUser::create([
                    'grade_id' => $grade_id,
                    'user_id' => $user['id'],
                ]);
            }
            DB::commit();

            return redirect()->back()->with('success', '个人资料更新成功！');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['message' => '提交失败，请联系管理员'])
                ->withInput();
        }
    }
}
