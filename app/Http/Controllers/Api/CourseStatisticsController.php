<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseChapter;
use Illuminate\Http\Request;
use App\Models\CourseStatistics;

class CourseStatisticsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_chapter_id' => 'required|integer|min:1',
            'duration' => 'required|integer',
            'position' => 'filled|integer'
        ], [], [
            'course_chapter_id' => '课程章节 id',
            'duration' => '时长,单位秒',
            'position' => '观看到位置,单位秒',
        ]);

        $user = $request->user();

        $data = $request->only(['course_chapter_id', 'duration', 'position']);

        $data['user_id'] = $user->id;

        $courseChapter = CourseChapter::query()->where('id', $data['course_chapter_id'])->first();
        if (!$courseChapter) {
            return response()->json(['message' => '章节不存在'], 403);
        }

        $courseStatistics = CourseStatistics::query()->where('course_chapter_id', $data['course_chapter_id'])->where('user_id', $data['user_id'])->first();
        if ($courseChapter) {
            $data['duration'] = $courseStatistics['duration'] + $data['duration'];
            $courseStatistics->update($data);
        } else {
            $data['course_id'] = $courseChapter['course_id'];
            $courseStatistics = CourseStatistics::create($data);
        }
        return response()->json($courseStatistics);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|min:1',
            'duration' => 'required|integer',
            'position' => 'filled|integer'
        ], [], [
            'id' => '课程 id',
            'duration' => '时长,单位秒',
            'position' => '观看到位置,单位秒',
        ]);

        $user = $request->user();

        $id  = $request->id;

        $data = $request->only(['duration', 'position']);

        $courseStatistics = CourseStatistics::query()->where('id', $id)->where('user_id', $user->id)->first();
        if (!$courseStatistics) {
            return response()->json(['message' => '课程统计不存在'], 403);
        }

        $data['duration'] = $courseStatistics['duration'] + $data['duration'];

        $courseStatistics->update($data);

        return response()->json($courseStatistics);
    }
}
