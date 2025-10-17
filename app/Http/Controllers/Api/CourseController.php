<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;

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

        $courses = Course::query()->where('status', 1)->orderByDesc('id')->simplePaginate($limit);

        return response()->json($courses);
    }

    public function detail(Request $request, $id)
    {
        $course = Course::query()->where('id', $id)->where('status', 1)->with(['chapters', 'tutor'])->first();

        return response()->json($course);
    }
}
