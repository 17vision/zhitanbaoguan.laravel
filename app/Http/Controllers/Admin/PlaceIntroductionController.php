<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlaceIntroduction;


class PlaceIntroductionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'limit' => 'filled|integer',
            'place_id' => 'required|integer|exists:places,id',
        ], [], [
            'limit' => '单页显示条数',
            'place_id' => '点位 id',
        ]);

        $limit = $request->input('limit', 30);

        $place_id = $request->input('place_id');

        $query = PlaceIntroduction::query()->where('place_id', $place_id)->orderByDesc('id')->orderBy('status', 'asc')->orderBy('sort');

        $placeIntroductions = $query->paginate($limit);

        return response()->json($placeIntroductions);
    }

    public function detail(Request $request, $id)
    {
        $placeIntroduction = PlaceIntroduction::where('id', $id)->with(['place'])->first();

        return response()->json($placeIntroduction);
    }

    public function store(Request $request)
    {
        $request->validate([
            'place_id' => 'required|integer|exists:places,id',
            'name' => 'required|string|max:16',
            'content' => 'required|string|max:2500',
            'voice' => 'filled|string|max:255',
            'status' => 'filled|in:1,2',
        ], [], [
            'place_id' => '点位 id',
            'name' => '介绍名',
            'content' => '介绍内容',
            'voice' => '音频文件',
            'status' => '状态',
        ]);

        $data = $request->only(['place_id', 'name', 'content', 'voice', 'status']);

        if (isset($data['voice'])) {
            $data['voice'] = ossToPath($data['voice']);
        }

        $placeIntroduction = PlaceIntroduction::create($data);

        return response()->json($placeIntroduction);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:place_introductions,id',
            'place_id' => 'filled|integer|exists:places,id',
            'name' => 'filled|string|max:16',
            'content' => 'filled|string|max:2500',
            'voice' => 'filled|string|max:255',
            'status' => 'filled|in:1,2',
        ], [], [
            'id' => '介绍 id',
            'place_id' => '点位 id',
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

        $placeIntroduction = PlaceIntroduction::query()->where('id', $request->id)->first();

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
            PlaceIntroduction::query()->where('id', $id)->update(['sort' => $index]);
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

        $delete = PlaceIntroduction::where('id', $id)->delete();

        return response()->json(['delete' => $delete]);
    }
}
