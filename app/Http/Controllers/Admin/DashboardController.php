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

        $data = [
            'course_count' => (int)$course['course_count'] ?? 0,
            'like_count' => (int)$course['like_count'] ?? 0,
            'collect_count' => (int)$course['collect_count'] ?? 0,
            'message_count' => (int)$course['message_count'] ?? 0,
        ];

        return response()->json($data);
    }
}
