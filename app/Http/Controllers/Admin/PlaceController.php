<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Place;
use App\Models\PlaceIntroduction;
use App\Models\PlaceMedia;
use App\Models\Venue;

class PlaceController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer',
            'venue_id' => 'required|integer|exists:venues,id',
            'parent_id' => 'filled|integer|exists:places,id'
        ], [], [
            'limit' => '单页显示条数',
            'venue_id' => '场馆 id',
            'parent_id' => '父点位 id',
        ]);

        $limit = $request->input('limit', 30);

        $venue_id = $request->input('venue_id');

        $parent_id = $request->input('parent_id');

        $query = Place::with(['introductions', 'medias'])->where('venue_id', $venue_id)->with(['father:id,parent_id,name'])->orderBy('status', 'asc')->orderBy('sort')->orderByDesc('id');

        if ($parent_id) {
            $query->where('parent_id', $parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        $places = $query->paginate($limit);
        foreach ($places as &$place) {
            $place['has_children'] = Place::query()->where('parent_id', $place['id'])->exists();
        }

        return response()->json($places);
    }

    public function detail(Request $request, $id)
    {
        $venue = Place::where('id', $id)->with(['introductions', 'medias'])->first();

        return response()->json($venue);
    }

    public function store(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
            'parent_id' => 'filled|integer|exists:places,id',
            'name' => 'required|string|max:16',
            'cover' => 'filled|string|max:255',
            'address' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:2500',
            'open_time' => 'filled|date_format:H:i:s',
            'close_time' => 'required_with:open_time|date_format:H:i:s|after:open_time',
            'longitude' => 'filled|numeric',
            'latitude' => 'filled|numeric',
            'tag' => 'filled|string',
            'qrcode' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'venue_id' => '场馆 id',
            'parent_id' => '父点位 id',
            'name' => '点位名',
            'cover' => '封面',
            'address' => '地址',
            'introduction' => '介绍',
            'open_time' => '开园时间',
            'close_time' => '闭园时间',
            'longitude' => '经度',
            'latitude' => '纬度',
            'tag' => '标签',
            'qrcode' => '小程序码',
            'status' => '状态',
        ]);

        $data = $request->only(['venue_id', 'parent_id', 'name', 'cover', 'address', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'tag', 'qrcode', 'status']);

        $venue = Venue::query()->where('id', $data['venue_id'])->first();

        $data['organization_id'] = $venue['organization_id'];

        if (isset($data['parent_id'])) {
            // Place::query()->with(['parents'])->get();
        } else {
            $data['level'] = 1;
        }

        if (isset($data['cover'])) {
            $data['cover'] = ossToPath($data['cover']);
        }

        $venue = Place::create($data);

        return response()->json($venue);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:places,id',
            'parent_id' => 'filled|integer|exists:places,id',
            'name' => 'filled|string|max:16',
            'cover' => 'filled|string|max:255',
            'address' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:2500',
            'open_time' => 'filled|date_format:H:i:s',
            'close_time' => 'required_with:open_time|date_format:H:i:s|after:open_time',
            'longitude' => 'filled|numeric',
            'latitude' => 'filled|numeric',
            'qrcode' => 'filled|string',
            'tag' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => '点位 id',
            'parent_id' => '父点位 id',
            'name' => '点位名',
            'cover' => '封面',
            'address' => '地址',
            'introduction' => '介绍',
            'open_time' => '开园时间',
            'close_time' => '闭园时间',
            'longitude' => '经度',
            'latitude' => '纬度',
            'qrcode' => '小程序码',
            'tag' => '标签',
            'status' => '状态',
        ]);

        $data = $request->only(['parent_id', 'name', 'cover', 'address', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'tag', 'qrcode', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请输入要更新的内容'], 403);
        }

        if (isset($data['parent_id'])) {
            Place::query()->with(['parents'])->get();
        }

        if (isset($data['cover'])) {
            $data['cover'] = ossToPath($data['cover']);
        }

        $venue = Place::query()->where('id', $request->id)->first();

        $venue->update($data);

        return response()->json($venue);
    }

    public function saveSort(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ], [], [
            'ids' => '点位 id',
        ]);

        $ids = $request->input('ids');

        foreach ($ids as $index => $id) {
            Place::query()->where('id', $id)->update(['sort' => $index]);
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

        if (Place::query()->where('parent_id', $id)->exists()) {
            return response()->json(['该点位还有子点位，不能删除'], 403);
        }

        if (PlaceIntroduction::query()->where('place_id', $id)->exists()) {
            return response()->json(['该点位还有介绍数据，不能删除'], 403);
        }

        if (PlaceMedia::query()->where('place_id', $id)->exists()) {
            return response()->json(['该点位还有媒体数据，不能删除'], 403);
        }

        $delete = Place::where('id', $id)->delete();

        return response()->json(['delete' => $delete]);
    }

    public function qrcode(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:places,id',
            'width' => 'filled|integer|min:240',
        ], [], [
            'id' => '点位 id',
            'width' => '小程序码宽度'
        ]);

        $id = $request->input('id');

        $place = Place::query()->where('id', $id)->with(['venue:id,qrcode_root'])->first();

        $qrcode_root = $place['venue']['qrcode_root'] ?? '';
        if (!$qrcode_root) {
            return response()->json(['message' => '请配置场馆小程序码根路径'], 403);
        }

        $hasChildren = Place::query()->where('parent_id', $id)->exists();
        if ($hasChildren) {
            $page = \sprintf("%s/pages/pavilion/part/list", $qrcode_root);
        } else {
            $page = \sprintf("%s/pages/pavilion/part/detail", $qrcode_root);
        }

        $data = [
            'scene' =>  \sprintf('id=%d', $id),
            'page' =>  $page,
            'check_path' => config('auth.wxmini.check_path'),
            'width' => $request->input('width', 640)
        ];
        $base64Image = app(ImageController::class)->getWxcode($data);
        if (!$base64Image) {
            return response()->json(['message' => '生成失败[1]'], 403);
        }

        // 将小程序码，存到 oss 上
        $request->replace([
            'info' => [
                'referer' => 'place',
                'use' => 'qrcode',
                'id' => $id
            ],
            'file' => $base64Image
        ]);

        $result = app(ImageController::class)->store($request);

        $data = $result->getData();

        if ($data->url) {
            Place::query()->where('id', $id)->update(['qrcode' => reverseStorageUrl($data->url) . '?time=' . time()]);
            return  $result;
        }
        return response()->json(['message' => '生成小程序码失败']);
    }
}
