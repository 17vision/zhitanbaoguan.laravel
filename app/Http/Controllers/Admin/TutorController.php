<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'username' => 'filled|string',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'username' => '名称',
        ]);

        $limit = $request->input('limit', 30);

        $username = $request->username;

        $query = Tutor::query();

        if ($username) {
            $query->where('username', 'like', '%' . $username . '%');
        }

        $courses = $query->paginate($limit);

        return response()->json($courses);
    }

    public function detail(Request $request, $id)
    {
        $role = Tutor::where('id', $id)->first();

        return response()->json($role);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' =>  'filled|integer',
            'username' => 'required|stirng|min:1|max:64',
            'avatar' => 'filled|string',
            'introduction' => 'filled|string',
        ], [], [
            'user_id' =>  '用户 id',
            'username' => '导师名称',
            'avatar' => '导师头像',
            'introduction' => '导师介绍',
        ]);

        $data = $request->only(['user_id', 'username', 'avatar', 'introduction']);

        $tutor = Tutor::create($data);

        return response()->json($tutor);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'user_id' =>  'filled|integer',
            'username' => 'filled|stirng|min:1|max:64',
            'avatar' => 'filled|string',
            'introduction' => 'filled|string',
        ], [], [
            'id' => '导师 id',
            'user_id' =>  '用户 id',
            'username' => '导师名称',
            'avatar' => '导师头像',
            'introduction' => '导师介绍',
        ]);

        $data = $request->only(['user_id', 'username', 'avatar', 'introduction']);

        $id = $request->id;

        $tutor = Tutor::query()->where('id', $id)->first();
        if (!$tutor) {
            return response()->json(['message' => '导师不存在'], 403);
        }

        $tutor->update($data);

        return response()->json($tutor);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '课程 id',
        ]);

        $id = $request->id;

        $course = Tutor::query()->where('id', $id)->first();
        if (!$course) {
            return response()->json(['message' => '课程不存在'], 403);
        }

        return response()->json($course);
    }
}
