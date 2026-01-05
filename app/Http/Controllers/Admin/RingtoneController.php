<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Ringtone;

class RingtoneController extends Controller
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

        $query = Ringtone::query();

        if ($name) {
            $name = trim($name);
            $name = "%{$name}%";
            $query->where('name', 'like', $name);
        }

        $ringtones = $query->orderByDesc('id')->paginate($limit);

        return response()->json($ringtones);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'introduction' => 'required|string',
            'path' => 'required|string',
            'thumbnail' => 'required|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'name' => '名称',
            'introduction' => '介绍',
            'thumbnail' => '图片',
            'path' => '地址',
            'status' => '状态',
        ]);

        $data = $request->only(['name', 'introduction', 'path', 'thumbnail', 'status']);

        $data['thumbnail'] = reverseStorageUrl($data['thumbnail']);

        $data['path'] = reverseStorageUrl($data['path']);

        $ringtone = Ringtone::create($data);

        return response()->json($ringtone);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'filled|string',
            'introduction' => 'filled|string',
            'thumbnail' => 'filled|string',
            'path' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => 'id',
            'name' => '名称',
            'introduction' => '介绍',
            'thumbnail' => '图片',
            'path' => '地址',
            'status' => '状态',
        ]);

        $data = $request->only(['name', 'introduction', 'thumbnail', 'path', 'status']);

        $id = $request->input('id');

        if (empty($data)) {
            return response()->json(['message' => '请提交数据'], 403);
        }

        $ringtone = Ringtone::query()->where('id', $id)->first();
        if (!$ringtone) {
            return response()->json(['message' => '主题不存在'], 403);
        }

        if (isset($data['path']) && $data['path']) {
            // Storage::disk('file')->delete(reverseStorageUrl($ringtone['path']));
            $data['path'] = reverseStorageUrl($data['path']);
        }

        if (isset($data['thumbnail']) && $data['thumbnail']) {
            // Storage::disk('file')->delete(reverseStorageUrl($ringtone['thumbnail']));
            $data['thumbnail'] = reverseStorageUrl($data['thumbnail']);
        }

        $media = $ringtone->update($data);

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

        $deletedRingtones = [];
        foreach ($ids as $id) {
            $ringtone = Ringtone::query()->where('id', $id)->first();
            if (!$ringtone) {
                continue;
            }

            if ($ringtone['thumbnail']) {
                Storage::disk('file')->delete(reverseStorageUrl($ringtone['thumbnail']));
            }

            if ($ringtone['path']) {
                Storage::disk('file')->delete(reverseStorageUrl($ringtone['path']));
            }

            $ringtone->delete();

            $deletedRingtones[] = $ringtone;
        }

        return response()->json(['ids' => $ids, 'deletedRingtones' => $deletedRingtones]);
    }
}
