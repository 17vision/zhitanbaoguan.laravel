<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;
use App\Rules\Phone;
use App\Models\Venue;

class VenueController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer'
        ], [], [
            'limit' => '单页显示条数',
        ]);

        $limit = $request->input('limit', 30);

        $venues = Venue::with(['organization:id,name'])->orderBy('status', 'asc')->orderByDesc('id')->paginate($limit);

        return response()->json($venues);
    }

    public function detail(Request $request, $id)
    {
        $venue = Venue::where('id', $id)->with(['organization:id,name', 'introductions', 'medias'])->first();

        return response()->json($venue);
    }

    public function store(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|integer|exists:organizations,id',
            'name' => 'required|string|max:16',
            'cover' => 'filled|string|max:255',
            'address' => 'filled|string|max:255',
            'phone' => [
                'filled',
                new Phone(),
            ],
            'introduction' => 'filled|string|max:2500',
            'open_time' => 'filled|date_format:H:i:s',
            'close_time' => 'required_with:open_time|date_format:H:i:s|after:open_time',
            'longitude' => 'filled|numeric',
            'latitude' => 'filled|numeric',
            'qrcode_root' => 'nullable|string',
            'qrcode' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'organization_id' => '组织 id',
            'name' => '场馆名',
            'cover' => '封面',
            'address' => '地址',
            'phone' => '手机号',
            'introduction' => '介绍',
            'open_time' => '开园时间',
            'close_time' => '闭园时间',
            'longitude' => '经度',
            'latitude' => '纬度',
            'qrcode_root' => '小程序码根路径',
            'qrcode' => '小程序码',
            'status' => '状态',
        ]);

        $data = $request->only(['organization_id', 'name', 'cover', 'address', 'phone', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'qrcode_root', 'qrcode', 'status']);

        if (isset($data['cover'])) {
            $data['cover'] = ossToPath($data['cover']);
        }

        $venue = Venue::create($data);

        return response()->json($venue);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:venues,id',
            'organization_id' => 'filled|integer|exists:organizations,id',
            'name' => 'filled|string|max:16',
            'cover' => 'filled|string|max:255',
            'address' => 'filled|string|max:255',
            'phone' => [
                'filled',
                new Phone(),
            ],
            'introduction' => 'filled|string|max:2500',
            'open_time' => 'filled|date_format:H:i:s',
            'close_time' => 'required_with:open_time|date_format:H:i:s|after:open_time',
            'longitude' => 'filled|numeric',
            'latitude' => 'filled|numeric',
            'qrcode_root' => 'nullable|string',
            'qrcode' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => '场馆 id',
            'organization_id' => '组织 id',
            'name' => '场馆名',
            'cover' => '封面',
            'address' => '地址',
            'phone' => '手机号',
            'introduction' => '介绍',
            'open_time' => '开园时间',
            'close_time' => '闭园时间',
            'longitude' => '经度',
            'latitude' => '纬度',
            'qrcode_root' => '小程序码根路径',
            'qrcode' => '小程序码',
            'status' => '状态',
        ]);

        $data = $request->only(['organization_id', 'name', 'cover', 'address', 'phone', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'qrcode_root', 'qrcode', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请输入要更新的内容'], 403);
        }

        if (isset($data['cover'])) {
            $data['cover'] = ossToPath($data['cover']);
        }

        $venue = Venue::query()->where('id', $request->id)->first();

        $venue->update($data);

        return response()->json($venue);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ], [], [
            'id' => 'id'
        ]);

        $id = $request->id;

        $delete = Venue::where('id', $id)->delete();

        if (Place::query()->where('venue_id', $id)->exists()) {
            return response()->json(['message' => '该场馆还有点位，不能删除'], 403);
        }

        return response()->json(['delete' => $delete]);
    }

    public function qrcode(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:venues,id',
            'width' => 'filled|integer|min:240',
        ], [], [
            'id' => '场馆 id',
            'width' => '小程序码宽度'
        ]);

        $id = $request->input('id');

        $venue = Venue::query()->where('id', $id)->first();

        $qrcode_root = $venue['qrcode_root'] ?? '';
        if (!$qrcode_root) {
            return response()->json(['message' => '请配置场馆小程序码根路径'], 403);
        }

        $page = \sprintf("%s/pages/pavilion/index", $qrcode_root);

        $data = [
            'scene' => \sprintf('id=%d', $id),
            'page' =>  $page,
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
                'referer' => 'venue',
                'use' => 'qrcode',
                'id' => $id
            ],
            'file' => $base64Image
        ]);

        $result = app(ImageController::class)->store($request);

        $data = $result->getData();

        if ($data->url) {
            $venue->update(['qrcode' => reverseStorageUrl($data->url) . '?time=' . time()]);
            return  $result;
        }
        return response()->json(['message' => '生成小程序码失败']);
    }
}
