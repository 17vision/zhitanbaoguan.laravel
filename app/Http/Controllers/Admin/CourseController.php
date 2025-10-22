<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Tutor;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'name' => 'filled|string',
            'status' => 'filled|in:0,1',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'name' => '名称',
            'status' => '状态',
        ]);

        $limit = $request->input('limit', 30);

        $title = $request->title;

        $query = Course::query()->with(['chapters.resource', 'tutor']);

        if (isset($request->status)) {
            $query->where('status', $request->status);
        }

        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        $courses = $query->paginate($limit);

        return response()->json($courses);
    }

    public function detail(Request $request, $id)
    {
        $role = Course::where('id', $id)->with(['chapters.resource', 'tutor'])->first();

        return response()->json($role);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:1|max:64',
            // 'duration' => 'required|integer',
            'category' => 'required|in:1,2,3,4',
            'difficulty' => 'required|in:1,2,3',
            'description' => 'filled|string|max:2500',
            'cover' => 'filled|string',
            'tutor_id' =>  'filled|integer',
            'status' => 'filled|in:0,1',
        ], [], [
            'title' => '标题',
            // 'duration' => '时长',
            'category' => '分类',
            'difficulty' => '难度',
            'description' => '描述',
            'cover' => '封面',
            'tutor_id' => '导师 id',
            'status' => '状态',
        ]);

        $user = $request->user();

        $data = $request->only(['title', 'duration', 'category', 'difficulty', 'description', 'cover', 'tutor_id', 'status']);

        $data['user_id'] = $user->id;

        if (isset($data['cover']) && $data['cover']) {
            $data['cover'] = reverseStorageUrl($data['cover']);
        }

        if (isset($data['tutor_id']) && $data['tutor_id']) {
            if (!Tutor::query()->where('id',  $data['tutor_id'])->exists()) {
                return response()->json(['message' => '导师不存在'], 403);
            }
        }

        $course = Course::create($data);

        return response()->json($course);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'title' => 'filled|string|min:1|max:64',
            'duration' => 'filled|integer',
            'category' => 'filled|in:1,2,3,4',
            'difficulty' => 'filled|in:1,2,3',
            'description' => 'filled|string|max:2500',
            'cover' => 'filled|string',
            'background' =>  'filled|string',
            'status' => 'filled|in:0,1',
        ], [], [
            'id' => '课程 id',
            'title' => '标题',
            'duration' => '时长',
            'category' => '分类',
            'difficulty' => '难度',
            'description' => '描述',
            'cover' => '封面',
            'background' => '背景',
            'status' => '状态',
        ]);

        $id = $request->id;

        $user = $request->user();

        $data = $request->only(['title', 'duration', 'category', 'difficulty', 'description', 'cover', 'tutor_id', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请提交有效数据'], 403);
        }

        $data['user_id'] = $user->id;

        if (isset($data['cover']) && $data['cover']) {
            $data['cover'] = reverseStorageUrl($data['cover']);
        }

        if (isset($data['tutor_id']) && $data['tutor_id']) {
            if (!Tutor::query()->where('id',  $data['tutor_id'])->exists()) {
                return response()->json(['message' => '导师不存在'], 403);
            }
        }

        $course = Course::query()->where('id', $id)->first();
        if (!$course) {
            return response()->json(['message' => '课程不存在'], 403);
        }

        $course->update($data);

        return response()->json($course);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '课程 id',
        ]);

        $id = $request->id;

        $course = Course::query()->where('id', $id)->first();
        if (!$course) {
            return response()->json(['message' => '课程不存在'], 403);
        }

        return response()->json($course);
    }
}
