<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use App\Models\GradeUser;
use App\Models\User;

class GradeUserController extends Controller
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

        $query = GradeUser::query()->with(['user']);

        if ($name) {
            $query->whereHas('name', function($query) use($name) {
                $query->where('nickname', 'like', '%' . $name . '%');
            });
        }

        $grades = $query->paginate($limit);

        return response()->json($grades);
    }

    public function detail(Request $request, $id)
    {
        $grade = GradeUser::query()->with(['user'])->where('id', $id)->first();

        return response()->json($grade);
    }

    public function store(Request $request)
    {
        $request->validate([
            'grade_id' =>  'required|integer|min:1',
            'user_id' =>  'required|integer|min:1',
        ], [], [
            'grade_id' => '班级 id',
            'user_id' => '用户 id'
        ]);

        $data = $request->only(['grade_id', 'user_id']);

        if (!Grade::query()->where('id',  $data['grade_id'])->exists()) {
            return response()->json(['message' => '班级不存在'], 403);
        }

        if (!User::query()->where('id',  $data['user_id'])->exists()) {
            return response()->json(['message' => '用户不存在'], 403);
        }

        $grade = GradeUser::query()->where($data)->first();
        if ($grade) {
            return response()->json(['message' => '班级用户已存在'], 403);
        }

        $gradeUser = GradeUser::create($data);

        return response()->json($gradeUser);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '课程 id',
        ]);

        $id = $request->id;

        $gradeUser = GradeUser::query()->where('id', $id)->first();
        if (!$gradeUser) {
            return response()->json(['message' => '班级用户不存在'], 403);
        }

        $gradeUser->delete();

        return response()->json($gradeUser);
    }
}
