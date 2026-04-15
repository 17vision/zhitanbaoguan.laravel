<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Place;

class PlaceController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer',
            'venue_id' => 'required|integer|exists:venues,id',
            'parent_id' => 'required|integer|exists:places,id'
        ], [], [
            'limit' => '单页显示条数',
            'venue_id' => '场馆 id',
            'parent_id' => '父点位 id',
        ]);

        $limit = $request->input('limit', 30);

        $venue_id = $request->input('venue_id');

        $parent_id = $request->input('parent_id');

        $query = Place::with(['introductions', 'medias'])->where('venue_id', $venue_id);

        if ($parent_id) {
            $query->where('parent_id', $parent_id);
        }

        $places = $query->paginate($limit);

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
            'organization_id' => 'required|integer|exists:organizations,id',
            'venue_id' => 'required|integer|exists:venues,id',
            'parent_id' => 'filled|integer|exists:places,id',
            'name' => 'required|string|max:16',
            'cover' => 'filled|string|max:255',
            'address' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:255',
            'open_time' => 'filled|date_format:H:i',
            'close_time' => 'required_with:open_time|date_format:H:i|after:open_time',
            'longitude' => 'filled|numeric',
            'latitude' => 'filled|numeric',
            'tag' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'organization_id' => '组织 id',
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
            'status' => '状态',
        ]);

        $data = $request->only(['organization_id', 'venue_id', 'parent_id', 'name', 'cover', 'address', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'tag', 'status']);

        if (isset($data['parent_id'])) {
            Place::query()->with(['parents'])->get();
        } else {
            $data['level'] = 1;
        }

        $venue = Place::create($data);

        return response()->json($venue);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:places,id',
            'organization_id' => 'filled|integer|exists:organizations,id',
            'venue_id' => 'filled|integer|exists:venues,id',
            'parent_id' => 'filled|integer|exists:places,id',
            'name' => 'filled|string|max:16',
            'cover' => 'filled|string|max:255',
            'address' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:255',
            'open_time' => 'filled|date_format:H:i',
            'close_time' => 'required_with:open_time|date_format:H:i|after:open_time',
            'longitude' => 'filled|numeric',
            'latitude' => 'filled|numeric',
            'tag' => 'filled|string',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => '点位 id',
            'organization_id' => '组织 id',
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
            'status' => '状态',
        ]);

        $data = $request->only(['organization_id', 'venue_id', 'parent_id', 'name', 'cover', 'address', 'introduction', 'open_time', 'close_time', 'longitude', 'latitude', 'tag', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请输入要更新的内容'], 403);
        }

        if (isset($data['parent_id'])) {
            Place::query()->with(['parents'])->get();
        }

        $venue = Place::query()->where('id', $request->id)->first();

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

        $delete = Place::where('id', $id)->delete();

        return response()->json(['delete' => $delete]);
    }
}
