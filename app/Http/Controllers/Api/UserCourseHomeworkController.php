<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseHomework;
use Illuminate\Http\Request;
use App\Models\UserCourseHomework;

class UserCourseHomeworkController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            // 'course_id' => 'required|integer|min:1',
            'course_homework_id' => 'required|integer|min:1',
            'content' => 'required|json|max:999',
        ], [], [
            // 'course_id' => '课程 id',
            'course_homework_id' => '作业 id',
            'content' => '内容',
        ]);

        $user = $request->user();

        $data = $request->only(['course_homework_id', 'content']);

        $data['user_id'] = $user->id;

        $courseHomework = CourseHomework::query()->where('id', $data['course_homework_id'])->first();
        if (!$courseHomework) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        if ($courseHomework['course_id']) {
            $data['course_id'] = $courseHomework['course_id'];
        }

        if (UserCourseHomework::query()->where('user_id', $user->id)->where('course_homework_id', $data['course_homework_id'])->exists()) {
            return response()->json(['message' => '您的作业已做过'], 403);
        }

        $courseHomework = UserCourseHomework::create($data);

        return response()->json($courseHomework);
    }
}
