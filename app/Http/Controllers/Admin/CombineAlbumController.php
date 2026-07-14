<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CombineAlbum;
use App\Models\CombineTemplate;
use App\Models\Venue;

class CombineAlbumController extends Controller
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

        $query = CombineAlbum::query()->where('venue_id', $venue_id)->orderBy('status', 'asc')->orderBy('sort')->orderByDesc('id');

        $albums = $query->paginate($limit);

        return response()->json($albums);
    }

    public function detail(Request $request, $id)
    {
        $album = CombineAlbum::where('id', $id)->first();

        return response()->json($album);
    }

    public function store(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
            'name' => 'required|string|max:32',
            'cover' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:2500',
            'sort' => 'filled|integer',
            'status' => 'filled|in:0,1,2',
        ], [], [
            'venue_id' => '场馆 id',
            'name' => '相册分类名称',
            'cover' => '分类封面图',
            'introduction' => '分类介绍文案',
            'sort' => '排序权重',
            'status' => '状态',
        ]);

        $data = $request->only(['venue_id', 'name', 'cover', 'introduction', 'sort', 'status']);

        $venue = Venue::query()->where('id', $data['venue_id'])->first();

        $data['organization_id'] = $venue['organization_id'];

        if (isset($data['cover'])) {
            $data['cover'] = ossToPath($data['cover']);
        }

        $album = CombineAlbum::create($data);

        return response()->json($album);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:combine_albums,id',
            'name' => 'filled|string|max:32',
            'cover' => 'filled|string|max:255',
            'introduction' => 'filled|string|max:2500',
            'sort' => 'filled|integer',
            'status' => 'filled|in:0,1,2',
        ], [], [
            'id' => '相册分类 id',
            'name' => '相册分类名称',
            'cover' => '分类封面图',
            'introduction' => '分类介绍文案',
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

        $album = CombineAlbum::query()->where('id', $request->id)->first();

        $album->update($data);

        return response()->json($album);
    }

    public function saveSort(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ], [], [
            'ids' => '相册分类 id',
        ]);

        $ids = $request->input('ids');

        foreach ($ids as $index => $id) {
            CombineAlbum::query()->where('id', $id)->update(['sort' => $index]);
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

        if (CombineTemplate::query()->where('combine_album_id', $id)->exists()) {
            return response()->json(['该相册分类还有模板数据，不能删除'], 403);
        }

        $delete = CombineAlbum::where('id', $id)->delete();

        return response()->json(['delete' => $delete]);
    }
}
