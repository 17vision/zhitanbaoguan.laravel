<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Grade;
use App\Models\User;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'name' => 'filled|string',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'name' => '名称',
        ]);

        $limit = $request->input('limit', 30);

        $name = $request->name;

        $query = Grade::query();

        if ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        $grades = $query->paginate($limit);

        return response()->json($grades);
    }

    public function detail(Request $request, $id)
    {
        $grade = Grade::where('id', $id)->first();

        return response()->json($grade);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:1|max:64',
            'description' => 'filled|string|max:250',
            'user_id' =>  'filled|integer',
        ], [], [
            'name' => '名称',
            'description' => '描述',
            'user_id' => '负责人'
        ]);

        $data = $request->only(['name', 'description', 'user_id']);

        if (isset($data['user_id']) && $data['user_id']) {
            if (!User::query()->where('id',  $data['user_id'])->exists()) {
                return response()->json(['message' => '负责人不存在'], 403);
            }
        }

        $grade = Grade::create($data);

        return response()->json($grade);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string|min:1|max:64',
            'description' => 'filled|string|max:250',
            'user_id' =>  'filled|integer',
        ], [], [
            'id' => '班级 id',
            'name' => '名称',
            'description' => '描述',
            'user_id' => '负责人'
        ]);

        $id = $request->id;

        $data = $request->only(['name', 'description', 'user_id']);

        if (empty($data)) {
            return response()->json(['message' => '请提交有效数据'], 403);
        }

        if (isset($data['user_id']) && $data['user_id']) {
            if (!User::query()->where('id',  $data['user_id'])->exists()) {
                return response()->json(['message' => '负责人不存在'], 403);
            }
        }

        $grade = Grade::query()->where('id', $id)->first();
        if (!$grade) {
            return response()->json(['message' => '班级不存在'], 403);
        }

        $grade->update($data);

        return response()->json($grade);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '课程 id',
        ]);

        $id = $request->id;

        $grade = Grade::query()->where('id', $id)->first();
        if (!$grade) {
            return response()->json(['message' => '班级不存在'], 403);
        }

        $grade->delete();

        return response()->json($grade);
    }
}
