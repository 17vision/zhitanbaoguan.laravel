<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ResourceGroup;

class ResourceGroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = ResourceGroup::query()->whereNull('parent_id')->with(['childs'])->orderByDesc('index')->get()->toArray();

        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:16',
            'parent_id' => 'filled|integer',
            'index' => 'filled|integer|min:0'
        ], [], [
            'name' => '名称',
            'parent_id' => '父 id',
            'index' => '排序'
        ]);

        $user = $request->user();

        $data = $request->only(['name', 'parent_id', 'index']);

        $data['user_id'] = $user->id;

        if (isset($data['parent_id']) && !ResourceGroup::query()->where('id', $data['parent_id'])->exists()) {
            return response()->json(['message' => '父分组不存在'], 403);
        }

        $resourceGroup =  ResourceGroup::create($data);

        return response()->json($resourceGroup);
    }
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'filled|string|max:16',
            'parent_id' => 'filled|integer',
            'index' => 'filled|integer|min:0',
        ], [], [
            'id' => '分组 id',
            'name' => '名称',
            'parent_id' => '父 id',
            'index' => '排序',
        ]);

        $id = $request->id;

        $user = $request->user();

        $data = $request->only(['name', 'parent_id', 'index']);

        $data['user_id'] = $user->id;


        if (isset($data['parent_id']) && !ResourceGroup::query()->where('id', $data['parent_id'])->exists()) {
            return response()->json(['message' => '父分组不存在'], 403);
        }

        $resourceGroup =  ResourceGroup::query()->where('id', $id)->first();
        if (!$resourceGroup) {
            return response()->json(['message' => '分组不存在'], 403);
        }

        $resourceGroup->update($data);

        return response()->json($resourceGroup);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ], [], [
            'id' => '分组 id',
        ]);

        $id = $request->id;

        $group = ResourceGroup::query()->where('id', $id)->first();
        if (!$group) {
            return response()->json(['message' => '分组不存在'], 403);
        }

        $result = $group->delete();

        return response()->json(['result' => $result]);
    }
}
