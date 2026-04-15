<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Rules\Phone;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer'
        ], [], [
            'limit' => '单页显示条数',
        ]);

        $limit = $request->input('limit', 30);

        $organizations = Organization::with(['user:id,nickname,account,phone,email,gender'])->paginate($limit);

        return response()->json($organizations);
    }

    public function detail(Request $request, $id)
    {
        $organization = Organization::where('id', $id)->with(['user:id,nickname,phone,email,gender'])->first();
        $organization['nickname'] = $organization['user']['nickname'];
        $organization['gender'] = $organization['user']['gender'];
        $organization['email'] = $organization['user']['email'];

        return response()->json($organization);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:16',
            'phone' => [
                'filled',
                new Phone(),
            ],
            'introduction' => 'filled|string|max:255',
            'status' => 'filled|in:1,2',
        ], [], [
            'name' => '组织名',
            'phone' => '手机号',
            'introduction' => '介绍',
            'status' => '状态',
        ]);

        $data = $request->only(['name', 'phone', 'introduction', 'status']);

        $user = $request->user();

        if (isset($data['phone'])) {
            if (Organization::query()->where('phone',   $data['phone'])->exists()) {
                return response()->json(['message' => '该手机号已使用，请更换手机号码'], 403);
            }
        }

        $data['user_id'] = $user->id;

        $organization = Organization::create($data);

        return response()->json($organization); 
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:organizations,id',
            'name' => 'filled|string|max:16',
            'phone' => [
                'filled',
                new Phone(),
            ],
            'introduction' => 'filled|string|max:255',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => '组织 id',
            'name' => '组织名',
            'phone' => '手机号',
            'introduction' => '介绍',
            'status' => '状态',
        ]);

        $data = $request->only(['name', 'phone', 'introduction', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请输入要更新的内容'], 403);
        }

        $organization = Organization::query()->where('id', $request->id)->first();

        $organization->update($data);

        return response()->json($organization); 
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ], [], [
            'id' => 'id'
        ]);

        $id = $request->id;

        $delete = Organization::where('id', $id)->delete();

        return response()->json(['delete' => $delete]);
    }
}
