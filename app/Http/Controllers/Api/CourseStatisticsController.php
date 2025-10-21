<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseStatistics;

class CourseStatisticsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
            'duration' => 'required|integer',
        ], [], [
            'course_id' => '课程 id',
            'duration' => '时长,单位秒',
        ]);

        $user = $request->user();

        $data = $request->only(['course_id', 'duration']);

        $data['user_id'] = $user->id;

        $courseStatistics = CourseStatistics::create($data);

        return response()->json($courseStatistics);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|min:1',
            'duration' => 'required|integer',
        ], [], [
            'id' => '课程 id',
            'duration' => '时长,单位秒',
        ]);

        $user = $request->user();

        $id  = $request->id;

        $duration = $request->duration;

        $courseStatistics = CourseStatistics::query()->where('id', $id)->where('user_id', $user->id)->first();
        if (!$courseStatistics) {
            return response()->json(['message' => '课程统计不存在'], 403);
        }

        $courseStatistics->update(['duration' => $duration]);

        return response()->json($courseStatistics);
    }
}
