<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserCourseHomework;

class UserCourseHomeworkController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
            'course_homework_id' => 'required|integer|min:1',
            'content' => 'required|json|max:999',
        ], [], [
            'course_id' => '课程 id',
            'course_homework_id' => '作业 id',
            'content' => '内容',
        ]);

        $user = $request->user();

        $data = $request->only(['course_id', 'course_homework_id', 'content']);

        $data['user_id'] = $user->id;

        $courseHomework = UserCourseHomework::create($data);

        return response()->json($courseHomework);
    }
}
