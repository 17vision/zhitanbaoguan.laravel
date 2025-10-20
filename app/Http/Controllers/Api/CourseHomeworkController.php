<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseHomework;

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

        $limit = $request->input('limit', 30);

        $query = CourseHomework::query();

        $homeworks = $query->simplePaginate($limit);

        return response()->json($homeworks);
    }
}
