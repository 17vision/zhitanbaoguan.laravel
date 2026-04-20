<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VenueIntroduction;

class VenueIntroductionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer',
            'venue_id' => 'required|integer|exists:venues,id',
        ], [], [
            'limit' => '单页显示条数',
            'venue_id' => '场馆 id',
        ]);

        $limit = $request->input('limit', 30);

        $venue_id = $request->input('venue_id');

        $query = VenueIntroduction::query()->where('venue_id', $venue_id)->orderBy('status', 'asc')->orderBy('sort')->orderByDesc('id');

        $venueIntroductions = $query->paginate($limit);

        return response()->json($venueIntroductions);
    }

    public function detail(Request $request, $id)
    {
        $placeIntroduction = VenueIntroduction::where('id', $id)->with(['venue'])->first();

        return response()->json($placeIntroduction);
    }

    public function store(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
            'name' => 'required|string|max:16',
            'content' => 'required|string|max:2500',
            'voice' => 'filled|string|max:255',
            'status' => 'filled|in:1,2',
        ], [], [
            'venue_id' => '场馆 id',
            'name' => '介绍名',
            'content' => '介绍内容',
            'voice' => '音频文件',
            'status' => '状态',
        ]);

        $data = $request->only(['venue_id', 'name', 'content', 'voice', 'status']);

        if (isset($data['voice'])) {
            $data['voice'] = ossToPath($data['voice']);
        }

        $placeIntroduction = VenueIntroduction::create($data);

        return response()->json($placeIntroduction);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:venue_introductions,id',
            'venue_id' => 'filled|integer|exists:places,id',
            'name' => 'filled|string|max:16',
            'content' => 'filled|string|max:2500',
            'voice' => 'filled|string|max:255',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => '介绍 id',
            'venue_id' => '场馆 id',
            'name' => '介绍名',
            'content' => '介绍内容',
            'voice' => '音频文件',
            'status' => '状态',
        ]);

        $data = $request->only(['name', 'content', 'voice', 'status']);

        if (empty($data)) {
            return response()->json(['message' => '请输入要更新的内容'], 403);
        }

        if (isset($data['voice'])) {
            $data['voice'] = ossToPath($data['voice']);
        }

        $placeIntroduction = VenueIntroduction::query()->where('id', $request->id)->first();

        $placeIntroduction->update($data);

        return response()->json($placeIntroduction);
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
            VenueIntroduction::query()->where('id', $id)->update(['sort' => $index]);
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

        $delete = VenueIntroduction::where('id', $id)->delete();

        return response()->json(['delete' => $delete]);
    }
}
