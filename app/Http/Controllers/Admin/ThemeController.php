<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Theme;

class ThemeController extends Controller
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

        $query = Theme::query();

        if ($name) {
            $name = trim($name);
            $name = "%{$name}%";
            $query->where('name', 'like', $name);
        }

        $themes = $query->orderByDesc('id')->paginate($limit);

        return response()->json($themes);
    }

    // 提交素材
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'path' => 'required|string',
            'introduction' => 'filled|string|max:255',
            'audio' => 'filled|string|max:255',
            'color' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'name' => '名称',
            'path' => '地址',
            'introduction' => '介绍',
            'audio' => '音频',
            'color' => '色值',
            'status' => '状态',
        ]);

        $data = $request->only(['name', 'introduction', 'path', 'audio', 'color', 'status']);

        $data['path'] = reverseStorageUrl($data['path']);

        if(isset($data['audio'])) {
            $data['audio'] = reverseStorageUrl($data['audio']);;
        }

        $theme = Theme::create($data);

        return response()->json($theme);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'filled|string',
            'introduction' => 'filled|string',
            'path' => 'filled|string',
            'audio' => 'filled|string',
            'color' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => 'id',
            'name' => '名称',
            'introduction' => '介绍',
            'path' => '地址',
            'audio' => '音频',
            'color' => '色值',
            'status' => '状态',
        ]);

        $data = $request->only(['name', 'introduction', 'path', 'audio', 'color', 'status']);

        $id = $request->input('id');

        if (empty($data)) {
            return response()->json(['message' => '请提交数据'], 403);
        }

        $theme = Theme::query()->where('id', $id)->first();
        if (!$theme) {
            return response()->json(['message' => '主题不存在'], 403);
        }

        if (isset($data['path']) && $data['path']) {
            // Storage::disk('file')->delete(reverseStorageUrl($theme['path']));
            $data['path'] = reverseStorageUrl($data['path']);
        }

        if(isset($data['audio'])) {
            $data['audio'] = reverseStorageUrl($data['audio']);;
        }

        $media = $theme->update($data);

        return response()->json(['result' => $media]);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'ids' => 'required|string'
        ], [], [
            'ids' => '主题 id'
        ]);

        $ids = $request->input('ids');

        $ids = explode(',', $ids);

        $deletedThemes = [];
        foreach ($ids as $id) {
            $theme = Theme::query()->where('id', $id)->first();
            if (!$theme) {
                continue;
            }

            if ($theme['path']) {
                Storage::disk('file')->delete(reverseStorageUrl($theme['path']));
            }

            $theme->delete();

            $deletedThemes[] = $theme;
        }

        return response()->json(['ids' => $ids, 'deletedThemes' => $deletedThemes]);
    }
}
