<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Scene;
use App\Models\SceneCategory;

class SceneController extends Controller
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

        $query = Scene::query();

        if ($name) {
            $name = trim($name);
            $name = "%{$name}%";
            $query->where('name', 'like', $name);
        }

        $scenes = $query->orderByDesc('id')->with(['sceneCategory'])->paginate($limit);

        return response()->json($scenes);
    }

    public function detail(Request $request, $id)
    {
        $scene = Scene::query()->where('id', $id)->with(['sceneCategory'])->first();

        return response()->json($scene);
    }

    // 提交场景
    public function store(Request $request)
    {
        $request->validate([
            'scene_category_id' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'introduction' => 'required|string|max:255',
            'image' => 'filled|string|max:255',
            'video' => 'filled|string|max:255',
            'tag' => 'filled|string|max:64'
        ], [], [
            'scene_category_id' => '组别 id',
            'name' => '名称',
            'introduction' => '介绍',
            'image' => '图片地址',
            'video' => '视频地址',
            'tag' => '标签',
        ]);


        $data = $request->only(['scene_category_id', 'name', 'introduction', 'image', 'video', 'tag']);

        if (isset($data['image']) && $data['image']) {
            $data['image'] = reverseStorageUrl($data['image']);
        }

        if (isset($data['video']) && $data['video']) {
            $data['video'] = reverseStorageUrl($data['video']);
        }

        $sceneCategory = SceneCategory::query()->where('id', $data['scene_category_id'])->first();
        if (!$sceneCategory) {
            return response()->json(['message' => '组别不存在'], 403);
        }

        $sceneCategory->increment('scene_nums');

        $scene = Scene::create($data);

        return response()->json($scene);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'scene_category_id' => 'filled|integer|min:1',
            'name' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:255',
            'image' => 'filled|string|max:255',
            'video' => 'filled|string|max:255',
            'tag' => 'filled|string|max:64'
        ], [], [
            'id' => 'id',
            'scene_category_id' => '组别 id',
            'name' => '名称',
            'introduction' => '介绍',
            'image' => '图片地址',
            'video' => '视频地址',
            'tag' => '标签',
        ]);

        $id = $request->input('id');

        $data = $request->only(['scene_category_id', 'name', 'introduction', 'image', 'video', 'tag']);

        if (empty($data)) {
            return response()->json(['message' => '请提交数据'], 403);
        }

        if (isset($data['image']) && $data['image']) {
            $data['image'] = reverseStorageUrl($data['image']);
        }

        if (isset($data['video']) && $data['video']) {
            $data['video'] = reverseStorageUrl($data['video']);
        }

        $scene = Scene::query()->where('id', $id)->first();
        if (!$scene) {
            return response()->json(['message' => '场景不存在'], 403);
        }

        if (isset($data['scene_category_id'])) {
            $sceneCategory = SceneCategory::query()->where('id', $data['scene_category_id'])->first();
            if (!$sceneCategory) {
                return response()->json(['message' => '组别不存在'], 403);
            }

            // 如果类别 id 不一样
            if ($scene['scene_category_id'] != $data['scene_category_id']) {
                $sceneCategory->increment('scene_nums');
                SceneCategory::query()->where('id', $scene['scene_category_id'])->decrement('scene_nums');
            }
        }

        $scene = $scene->update($data);

        return response()->json($scene);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|string'
        ], [], [
            'id' => '场景 id'
        ]);

        $id = $request->input('id');

        $scene = Scene::query()->where('id', $id)->first();
        if (!$scene) {
            return response()->json(['message' => '场景不存在'], 403);
        }

        if ($scene['image']) {
            Storage::disk('file')->delete(reverseStorageUrl($scene['image']));
        }

        if ($scene['video']) {
            Storage::disk('file')->delete(reverseStorageUrl($scene['video']));
        }

        // 分组人减少
        SceneCategory::query()->where('id', $scene['scene_category_id'])->decrement('scene_nums');

        $scene->delete();

        return response()->json($scene);
    }
}
