<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseHomework;
use App\Models\UserCourseHomework;

class CourseHomeworkController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
        ]);

        $user = $request->user();

        $limit = $request->input('limit', 30);

        $homeworks = CourseHomework::query()->simplePaginate($limit);

        $homeworks->getCollection()->transform(function ($homework) use ($user) {
            if ($user) {
                $homework['did'] = UserCourseHomework::query()->where('user_id', $user->id)->where('course_homework_id', $homework->id)->exists();
            } else {
                $homework['did'] = false;
            }
            return $homework;
        });

        return response()->json($homeworks);
    }

    // 详情
    public function detail(Request $request, $id)
    {
        $courseHomework = CourseHomework::query()->where('id', $id)->first();

        $user = $request->user();

        if (!$courseHomework) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        if ($user) {
            $courseHomework['did_homework'] = UserCourseHomework::query()->where('user_id', $user->id)->where('course_homework_id', $courseHomework->id)->first();
        } else {
            $courseHomework['did_homework'] = null;
        }
        return response()->json($courseHomework);
    }
}
