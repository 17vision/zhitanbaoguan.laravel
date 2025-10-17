<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CourseCollect;
use App\Models\CourseLike;

class CourseController extends Controller
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

        $limit = $request->input('limit', 30);

        $user = $request->user();

        $courses = Course::query()->where('status', 1)->orderByDesc('id')->simplePaginate($limit);

        if ($user) {
            $ids = [];
            foreach ($courses as $course) {
                array_push($ids, $course->id);
            }

            $likeIds = CourseLike::query()->where('user_id', $user->id)->whereIn('course_id', $ids)->pluck('course_id')->flip();
            $collectIds = CourseCollect::query()->where('user_id', $user->id)->whereIn('course_id', $ids)->pluck('course_id')->flip();
            $courses->getCollection()->transform(function ($course) use ($likeIds, $collectIds) {
                $course->liked = isset($likeIds[$course->id]);
                $course->collected = isset($collectIds[$course->id]);
                return $course;
            });
        } else {
            $courses->getCollection()->transform(function ($course) {
                $course->liked = false;
                $course->collected = false;
                return $course;
            });
        }

        return response()->json($courses);
    }

    public function detail(Request $request, $id)
    {
        $user = $request->user();

        $course = Course::query()->where('id', $id)->where('status', 1)->with(['chapters', 'tutor'])->first();

        if ($user) {
            $course['liked'] = CourseLike::query()->where('course_id', $course->id)->where('user_id', $user->id)->exists();
            $course['collected'] = CourseCollect::query()->where('course_id', $course->id)->where('user_id', $user->id)->exists();
        } else {
            $course['liked'] = false;
            $course['collected'] = false;
        }

        return response()->json($course);
    }
}
