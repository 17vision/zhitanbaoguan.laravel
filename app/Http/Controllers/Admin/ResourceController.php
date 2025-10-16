<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'name' => 'filled|string',
            'resource_group_id' => 'filled|integer',
            'type' => 'filled|in:1,2,3,4',
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
        ], [], [
            'name' => '名称',
            'resource_group_id' => '分组 id',
            'type' => '分类',
            'limit' => '单页显示条数',
            'page' => '当前页',
        ]);

        $limit = $request->input('limit', 30);

        $name = $request->name;

        $type = $request->type;

        $resource_group_id = $request->resource_group_id;

        $query = Resource::query();

        if ($resource_group_id) {
            $query->where('resource_group_id', $resource_group_id);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($name) {
            $name = trim($name);
            $name = "%{$name}%";
            $query->where('name', 'like', $name);
        }

        $query->with(['group:id,name']);

        $resources = $query->paginate($limit);

        return response()->json($resources);
    }

    // 提交素材
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:1,2,3,4',
            'name' => 'required|string',
            'path' => 'required|string',
            'thumbnail' => 'filled|string',
            'resource_group_id' => 'filled|integer',
        ], [], [
            'name' => '名称',
            'type' => '分类',
            'path' => '地址',
            'thumbnail' => '封面图',
            'resource_group_id' => '分组 id',
        ]);

        $data = $request->only(['type', 'resource_group_id', 'name', 'path', 'thumbnail']);

        $user = $request->user();

        $data['user_id'] = $user->id;

        $data['path'] = reverseStorageUrl($data['path']);

        if (isset($data['thumbnail']) && $data['thumbnail']) {
            $data['thumbnail'] = reverseStorageUrl($data['thumbnail']);
        }

        $resource = Resource::create($data);

        return response()->json($resource);
    }

    // 修改素材
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'type' => 'filled|in:1,2,3,4',
            'resource_group_id' => 'filled|integer',
            'name' => 'filled|string',
            'path' => 'filled|string',
            'thumbnail' => 'filled|string'
        ], [], [
            'id' => '资源 id',
            'name' => '名称',
            'resource_group_id' => '分组 id',
            'type' => '分类',
            'path' => '地址',
            'thumbnail' => '封面图',
        ]);

        $id = $request->id;

        $data = $request->only(['type', 'resource_group_id', 'name', 'path', 'thumbnail']);

        if (empty($data)) {
            return response()->json(['message' => '请提交数据'], 403);
        }

        $user = $request->user();

        $resource = Resource::query()->where('id', $id)->first();
        if (!$resource) {
            return response()->json(['message' => '资源不存在'], 403);
        }

        $data['user_id'] = $user->id;

        if (isset($data['path']) && $data['path']) {
            Storage::disk('file')->delete(reverseStorageUrl($resource['path']));

            $data['path'] = reverseStorageUrl($data['path']);
        }

        if (isset($data['thumbnail']) && $data['thumbnail']) {
            if ($resource['thumbnail']) {
                Storage::disk('file')->delete(reverseStorageUrl($resource['thumbnail']));
            }

            $data['thumbnail'] = reverseStorageUrl($data['thumbnail']);
        }

        $media = $resource->update($data);

        return response()->json(['result' => $media]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required_without:ids|integer',
            'ids' => 'required_without:id|array'
        ], [], [
            'id' => 'id',
            'ids' => 'ids'
        ]);

        $id = $request->id;

        $ids = $request->ids;

        return response()->json(['id' => $id, 'ids' => $ids]);
    }
}
