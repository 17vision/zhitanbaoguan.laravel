<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseCollect;
use Illuminate\Http\Request;

class CourseCollectController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
        ], [], [
            'course_id' => '课程 id'
        ]);

        $course_id = $request->course_id;

        $user = $request->user();

        $courseCollect = CourseCollect::query()->where('course_id', $course_id)->where('user_id', $user->id)->first();

        if ($courseCollect) {
            $result = $courseCollect->delete();
        } else {
            $result = CourseCollect::create([
                'course_id'=> $course_id,
                'user_id' => $user->id
            ]);
        }
        return response()->json($result);
    }
}
