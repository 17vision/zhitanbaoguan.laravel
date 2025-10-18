<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseHomework;
use Illuminate\Http\Request;

class CourseHomeworkController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
        ], [], [
            'course_id' => '课程 id',
        ]);

        $course_id = $request->course_id;

        $courseHomeworks = CourseHomework::query()->where('course_id', $course_id)->get();

        return response()->json($courseHomeworks);
    }

    public function detail(Request $request, $id)
    {
        $courseHomework = CourseHomework::where('id', $id)->first();

        return response()->json($courseHomework);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
            'title' => 'required|string|max:64',
            'content' => 'required|string|max:200',
            'config' => 'required|json|max:999',
        ], [], [
            'course_id' => '课程 id',
            'title' => '课程 id',
            'content' => '内容',
            'config' => '配置',
        ]);

        $user = $request->user();

        $data = $request->only(['course_id', 'title', 'content', 'config']);

        $data['user_id'] = $user->id;

        $courseHomework = CourseHomework::create($data);

        return response()->json($courseHomework);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'course_id' => 'filled|integer|min:1',
            'title' => 'filled|string|max:64',
            'content' => 'filled|string|max:200',
            'config' => 'filled|json|max:999',
        ], [], [
            'id' => '作业 id',
            'course_id' => '课程 id',
            'title' => '课程 id',
            'content' => '内容',
            'config' => '配置',
        ]);

        $user = $request->user();

        $id = $request->id;

        $data = $request->only(['course_id', 'title', 'content', 'config']);

        $data['user_id'] = $user->id;

        $courseHomework = CourseHomework::query()->where('id', $id)->first();
        if (!$courseHomework) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        $courseHomework->update($data);

        return response()->json($courseHomework);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '作业 id',
        ]);

        $id = $request->id;

        $courseHomework = CourseHomework::query()->where('id', $id)->first();
        if (!$courseHomework) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        $courseHomework->delete();

        return response()->json($courseHomework);
    }
}
