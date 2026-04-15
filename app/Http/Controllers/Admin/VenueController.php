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

        $venues = Venue::with(['organization:id,name'])->paginate($limit);

        return response()->json($venues);
    }

    public function detail(Request $request, $id)
    {
        $venue = Venue::where('id', $id)->with(['organization:id,name'])->first();

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
            'introduction' => 'filled|string|max:255',
            'open_time' => 'filled|date_format:H:i',
            'close_time' => 'required_with:open_time|date_format:H:i|after:open_time',
            'longitude' => 'filled|numeric',
            'latitude' => 'filled|numeric',
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
            'status' => '状态',
        ]);

        $data = $request->only(['organization_id', 'name', 'cover', 'address', 'phone', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'status']);

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
            'introduction' => 'filled|string|max:255',
            'open_time' => 'filled|date',
            'close_time' => 'required_with:open_time|date|after:open_time',
            'longitude' => 'filled|numeric',
            'latitude' => 'filled|numeric',
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
            'status' => '状态',
        ]);

        $data = $request->only(['organization_id', 'name', 'cover', 'address', 'phone', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请输入要更新的内容'], 403);
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
}
