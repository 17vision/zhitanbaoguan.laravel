<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function basic_info()
    {
        $course = Course::query()
            ->selectRaw('COUNT(*) as course_count, SUM(like_count) as like_count, SUM(collect_count) as collect_count, SUM(message_count) as message_count')
            ->where('status', 1)
            ->first();

        return response()->json($course);
    }
}
