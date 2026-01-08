<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scene;
use Illuminate\Http\Request;
use App\Models\SceneCategory;

class SceneCategoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'name' => 'filled|string',
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
        ], [], [
            'name' => '名称',
            'limit' => '单页显示条数',
            'page' => '当前页',
        ]);

        $limit = $request->input('limit', 30);

        $name = $request->input('name');

        $query = SceneCategory::query();

        if ($name) {
            $name = trim($name);
            $name = "%{$name}%";
            $query->where('name', 'like', $name);
        }

        $sceneCategories = $query->orderByDesc('id')->paginate($limit);

        return response()->json($sceneCategories);
    }

    // 提交素材
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'introduction' => 'filled|string|max:255',
        ], [], [
            'name' => '名称',
            'introduction' => '介绍',
        ]);

        $data = $request->only(['name', 'introduction']);

        $sceneCategory = SceneCategory::create($data);

        return response()->json($sceneCategory);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:255',
        ], [], [
            'id' => 'id',
            'name' => '名称',
            'introduction' => '介绍'
        ]);

        $id = $request->input('id');

        $data = $request->only(['name', 'introduction']);

        if (empty($data)) {
            return response()->json(['message' => '请提交数据'], 403);
        }

        $sceneCategory = SceneCategory::query()->where('id', $id)->first();
        if (!$sceneCategory) {
            return response()->json(['message' => '场景类别不存在'], 403);
        }

        $sceneCategory = $sceneCategory->update($data);

        return response()->json($sceneCategory);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|string'
        ], [], [
            'id' => '场景类别 id'
        ]);

        $id = $request->input('id');

        $sceneCategory = SceneCategory::query()->where('id', $id)->first();
        if (!$sceneCategory) {
            return response()->json(['message' => '场景类别不存在'], 403);
        }

        if (Scene::query()->where('scene_category_id', $id)->exists()) {
            return response()->json(['message' => '不能删除，还有场景在使用场景类别'], 403);
        }

        $sceneCategory->delete();

        return response()->json($sceneCategory);
    }
}
