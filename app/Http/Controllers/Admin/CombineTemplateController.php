<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CombineTemplate;
use App\Models\CombinePhoto;

class CombineTemplateController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer',
            'combine_album_id' => 'required|integer|exists:combine_albums,id',
        ], [], [
            'limit' => '单页显示条数',
            'combine_album_id' => '相册专辑 id',
        ]);

        $limit = $request->input('limit', 30);

        $combine_album_id = $request->input('combine_album_id');

        $query = CombineTemplate::query()->where('combine_album_id', $combine_album_id)->orderBy('status', 'asc')->orderBy('sort')->orderByDesc('id');

        $templates = $query->paginate($limit);

        return response()->json($templates);
    }

    public function detail(Request $request, $id)
    {
        $template = CombineTemplate::where('id', $id)->first();

        return response()->json($template);
    }

    public function store(Request $request)
    {
        $request->validate([
            'combine_album_id' => 'required|integer|exists:combine_albums,id',
            'name' => 'required|string|max:32',
            'cover' => 'required|string|max:255',
            'introduction' => 'filled|string|max:2500',
            'sort' => 'filled|integer',
            'status' => 'filled|in:1,2',
        ], [], [
            'combine_album_id' => '相册分类 id',
            'name' => '模板名称',
            'cover' => '模板封面图',
            'introduction' => '模板介绍文案',
            'sort' => '排序权重',
            'status' => '状态',
        ]);

        $data = $request->only(['combine_album_id', 'name', 'cover', 'introduction', 'sort', 'status']);

        $data['cover'] = ossToPath($data['cover']);

        $template = CombineTemplate::create($data);

        return response()->json($template);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:combine_templates,id',
            'name' => 'filled|string|max:32',
            'cover' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:2500',
            'sort' => 'filled|integer',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => '模板 id',
            'name' => '模板名称',
            'cover' => '模板封面图',
            'introduction' => '模板介绍文案',
            'sort' => '排序权重',
            'status' => '状态',
        ]);

        $data = $request->only(['name', 'cover', 'introduction', 'sort', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请输入要更新的内容'], 403);
        }

        if (isset($data['cover'])) {
            $data['cover'] = ossToPath($data['cover']);
        }

        $template = CombineTemplate::query()->where('id', $request->id)->first();

        $template->update($data);

        return response()->json($template);
    }

    public function saveSort(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ], [], [
            'ids' => '模板 id',
        ]);

        $ids = $request->input('ids');

        foreach ($ids as $index => $id) {
            CombineTemplate::query()->where('id', $id)->update(['sort' => $index]);
        }

        return response()->json(['message' => '已排序']);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ], [], [
            'id' => 'id'
        ]);

        $id = $request->id;

        if (CombinePhoto::query()->where('combine_template_id', $id)->exists()) {
            return response()->json(['该模板还有合成照片数据，不能删除'], 403);
        }

        $delete = CombineTemplate::where('id', $id)->delete();

        return response()->json(['delete' => $delete]);
    }

    public function qrcode(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:combine_templates,id',
            'width' => 'filled|integer|min:240',
        ], [], [
            'id' => '模板 id',
            'width' => '小程序码宽度'
        ]);

        $id = $request->input('id');

        $template = CombineTemplate::query()->where('id', $id)->with(['combineAlbum.venue:id,qrcode_root'])->first();

        $venue = $template['combineAlbum']['venue'] ?? null;
        $qrcode_root = $venue['qrcode_root'] ?? '';
        if (!$qrcode_root) {
            return response()->json(['message' => '请配置场馆小程序码根路径'], 403);
        }

        $venue_id = $template['combineAlbum']['venue_id'] ?? 0;

        $page = \sprintf("%s/pages/photo/part/create", $qrcode_root);

        $data = [
            'scene' => \sprintf('venue_id=%d&id=%d', $venue_id, $id),
            'page' => $page,
            'check_path' => config('auth.wxmini.check_path'),
            'width' => $request->input('width', 640)
        ];
        $base64Image = app(ImageController::class)->getWxcode($data);
        if (!$base64Image) {
            return response()->json(['message' => '生成失败[1]'], 403);
        }

        // 将小程序码，存到本地
        $request->replace([
            'info' => [
                'referer' => 'template',
                'use' => 'qrcode',
                'id' => $id
            ],
            'file' => $base64Image
        ]);

        $result = app(ImageController::class)->store($request);

        $data = $result->getData();

        if ($data->url) {
            $template->update(['qrcode' => reverseStorageUrl($data->url) . '?time=' . time()]);
            return $result;
        }
        return response()->json(['message' => '生成小程序码失败']);
    }
}
