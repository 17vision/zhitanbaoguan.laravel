<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\Resource;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
            'title' => 'filled|string',
            'status' => 'filled|in:0,1',
            'homework_group_id' => 'filled|integer|min:1',
        ], [], [
            'page' => '当前页',
            'limit' => '单页显示条数',
            'title' => '名称',
            'status' => '状态',
            'homework_group_id' => '课程分组 id'
        ]);

        $limit = $request->input('limit', 30);

        $title = $request->title;

        $homework_group_id = $request->homework_group_id;

        $query = Homework::query()->with(['resource']);

        if ($homework_group_id) {
            $query->where('homework_group_id', $homework_group_id);
        }

        if (isset($request->status)) {
            $query->where('status', $request->status);
        }

        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        $homeworks = $query->simplePaginate($limit);

        return response()->json($homeworks);
    }

    public function detail(Request $request, $id)
    {
        $homework = Homework::where('id', $id)->with(['resource', 'group'])->first();

        return response()->json($homework);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:64',
            'content' => 'required|string|max:200',
            'config' => 'required|json|max:999',
            'status' => 'filled|in:0,1',
            'homework_group_id' => 'filled|integer|min:1',
            'resource_id' =>  'filled|integer',
        ], [], [
            'homework_group_id' => '分组 id',
            'title' => '课程 id',
            'content' => '内容',
            'config' => '配置',
            'status' => '状态',
            'resource_id' => '资源 id',
        ]);

        $user = $request->user();

        $data = $request->only(['homework_group_id', 'title', 'content', 'config', 'resource_id', 'status']);

        $data['user_id'] = $user->id;

        if (isset($data['resource_id']) && !Resource::query()->where('id', $data['resource_id'])->exists()) {
            return response()->json([
                'message' => '资源不存在'
            ]);
        }

        $homework = Homework::create($data);

        return response()->json($homework);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'title' => 'filled|string|max:64',
            'content' => 'filled|string|max:200',
            'config' => 'filled|json|max:999',
            'status' => 'filled|in:0,1',
            'homework_group_id' => 'filled|integer|min:1',
            'resource_id' =>  'nullable|integer',
        ], [], [
            'id' => '作业 id',
            'homework_group_id' => '分组 id',
            'title' => '课程 id',
            'content' => '内容',
            'config' => '配置',
            'status' => '状态',
            'resource_id' => '资源 id',
        ]);

        $id = $request->id;
        
        $user = $request->user();
        
        $data['user_id'] = $user->id;

        $homework = Homework::query()->where('id', $id)->first();
        if (!$homework) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        if (isset($data['resource_id']) && !Resource::query()->where('id', $data['resource_id'])->exists()) {
            return response()->json([
                'message' => '资源不存在'
            ]);
        }

        $homework->update($data);

        return response()->json($homework);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '作业 id',
        ]);

        $id = $request->id;

        $homework = Homework::query()->where('id', $id)->first();
        if (!$homework) {
            return response()->json(['message' => '作业不存在'], 403);
        }

        $homework->delete();

        return response()->json($homework);
    }
}
