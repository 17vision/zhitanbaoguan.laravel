<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeworkGroup;
use Illuminate\Http\Request;

class HomeworkGroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = HomeworkGroup::query()->whereNull('parent_id')->with(['childs'])->orderByDesc('index')->get()->toArray();

        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:16',
            'description' => 'filled|string|max:250',
            'parent_id' => 'filled|integer',
            'index' => 'filled|integer|min:0'
        ], [], [
            'name' => '名称',
            'description' => '描述',
            'parent_id' => '父 id',
            'index' => '排序'
        ]);

        $user = $request->user();

        $data = $request->only(['name', 'description', 'parent_id', 'index']);

        $data['user_id'] = $user->id;

        if (isset($data['parent_id']) && !HomeworkGroup::query()->where('id', $data['parent_id'])->exists()) {
            return response()->json(['message' => '父分组不存在'], 403);
        }

        $homeworkGroup =  HomeworkGroup::create($data);

        return response()->json($homeworkGroup);
    }
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'filled|string|max:16',
            'description' => 'filled|string|max:250',
            'parent_id' => 'filled|integer',
            'index' => 'filled|integer|min:0',
        ], [], [
            'id' => '分组 id',
            'name' => '名称',
            'description' => '描述',
            'parent_id' => '父 id',
            'index' => '排序',
        ]);

        $id = $request->id;

        $user = $request->user();

        $data = $request->only(['name', 'description', 'parent_id', 'index']);

        $data['user_id'] = $user->id;


        if (isset($data['parent_id']) && !HomeworkGroup::query()->where('id', $data['parent_id'])->exists()) {
            return response()->json(['message' => '父分组不存在'], 403);
        }

        $homeworkGroup =  HomeworkGroup::query()->where('id', $id)->first();
        if (!$homeworkGroup) {
            return response()->json(['message' => '分组不存在'], 403);
        }

        $homeworkGroup->update($data);

        return response()->json($homeworkGroup);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ], [], [
            'id' => '分组 id',
        ]);

        $id = $request->id;

        $group = HomeworkGroup::query()->where('id', $id)->first();
        if (!$group) {
            return response()->json(['message' => '分组不存在'], 403);
        }

        $result = $group->delete();

        return response()->json(['result' => $result]);
    }
}
