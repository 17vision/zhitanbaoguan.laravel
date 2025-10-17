<?php

namespace App\Http\Controllers\Api;

use App\Models\CourseLike;
use Illuminate\Http\Request;

class CourseLikeController extends Controller
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

        $courseLike = CourseLike::query()->where('course_id', $course_id)->where('user_id', $user->id)->first();

        if ($courseLike) {
            $result = $courseLike->delete();
        } else {
            $result = CourseLike::create([
                'course_id' => $course_id,
                'user_id' => $user->id
            ]);
        }
        return response()->json($result);
    }
}
