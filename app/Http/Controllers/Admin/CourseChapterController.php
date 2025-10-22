<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\Resource;
use Illuminate\Http\Request;

class CourseChapterController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
        ], [], [
            'course_id' => '课程 id',
        ]);

        $course_id = $request->course_id;

        $courseChapters = CourseChapter::query()->where('course_id', $course_id)->with(['resource'])->orderByDesc('index')->get();

        return response()->json($courseChapters);
    }

    public function detail(Request $request, $id)
    {
        $courseChapter = CourseChapter::where('id', $id)->with(['resource'])->first();

        return response()->json($courseChapter);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|min:1',
            'title' => 'required|string|min:1|max:64',
            'description' => 'filled|string|max:3500',
            'duration' => 'required|integer',
            'background' => 'required|string',
            'resource_id' =>  'filled|integer',
            'index' => 'filled|integer|min:0',
        ], [], [
            'course_id' => '课程 id',
            'title' => '标题',
            'description' => '描述',
            'duration' => '时长,单位秒',
            'background' => '背景图片',
            'resource_id' => '资源 id',
            'index' => '排序 index',
        ]);

        $user = $request->user();

        $data = $request->only(['course_id', 'title', 'description', 'duration', 'background', 'resource_id', 'index']);

        $data['user_id'] = $user->id;

        if (isset($data['background']) && $data['background']) {
            $data['background'] = reverseStorageUrl($data['background']);
        }

        if (!Course::query()->where('id', $data['course_id'])->exists()) {
            return response()->json([
                'message' => '课程不存在'
            ]);
        }

        if (isset($data['resource_id']) && !Resource::query()->where('id', $data['resource_id'])->exists()) {
            return response()->json([
                'message' => '资源不存在'
            ]);
        }

        $courseChapter = CourseChapter::create($data);

        // 更新课程时间
        $duration = CourseChapter::query()->where('course_id', $data['course_id'])->sum('duration');

        Course::query()->where('id', $data['course_id'])->update(['duration' => $duration]);

        return response()->json($courseChapter);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|min:1',
            'course_id' => 'filled|integer|min:1',
            'title' => 'filled|string|min:1|max:64',
            'description' => 'filled|string|max:3500',
            'duration' => 'filled|integer',
            'background' => 'filled|string',
            'resource_id' =>  'filled|integer',
            'index' => 'filled|integer|min:0',
        ], [], [
            'id' => '章节 id',
            'course_id' => '课程 id',
            'title' => '标题',
            'description' => '描述',
            'duration' => '时长,单位秒',
            'background' => '背景图片',
            'resource_id' => '资源 id',
            'index' => '排序 index',
        ]);

        $user = $request->user();

        $id = $request->id;

        $data = $request->only(['course_id', 'title', 'description', 'duration', 'background', 'resource_id', 'index']);

        if (empty($data)) {
            return response()->json(['message' => '请提交有效数据'], 403);
        }

        $data['user_id'] = $user->id;

        if (isset($data['background']) && $data['background']) {
            $data['background'] = reverseStorageUrl($data['background']);
        }

        if (isset($data['course_id']) && !Course::query()->where('id', $data['course_id'])->exists()) {
            return response()->json([
                'message' => '课程不存在'
            ], 403);
        }

        if (isset($data['resource_id']) && !Resource::query()->where('id', $data['resource_id'])->exists()) {
            return response()->json([
                'message' => '资源不存在'
            ], 403);
        }

        $courseChapter = CourseChapter::query()->where('id', $id)->first();
        if (!$courseChapter) {
            return response()->json(['message' => '章节不存在'], 403);
        }

        $courseChapter->update($data);

        if (isset($data['duration'])) {
            $duration = CourseChapter::query()->where('course_id', $courseChapter['course_id'])->sum('duration');

            Course::query()->where('id', $courseChapter['course_id'])->update(['duration' => $duration]);
        }

        return response()->json($courseChapter);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|min:1'
        ], [], [
            'id' => '章节 id'
        ]);

        $id = $request->id;

        $courseChapter = CourseChapter::query()->where('id', $id)->first();
        if (!$courseChapter) {
            return response()->json(['message' => '章节不存在'], 403);
        }

        $result = $courseChapter->delete();

        return response()->json(['result' => $result]);
    }
}
