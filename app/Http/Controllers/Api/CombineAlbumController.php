<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CombineAlbum;

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

        $limit = $request->input('limit', 100);

        $venue_id = $request->input('venue_id');

        $query = CombineAlbum::with(['templates' => function ($query) {
            $query->where('status', 1)->orderBy('sort')->orderByDesc('id');
        }])
            ->where('venue_id', $venue_id)
            ->where('status', 1)
            ->whereHas('templates', function ($query) {
                $query->where('status', 1);
            })
            ->orderBy('sort')
            ->orderByDesc('id');

        $albums = $query->simplePaginate($limit);

        return response()->json($albums);
    }

    public function detail(Request $request, $id)
    {
        $album = CombineAlbum::where('id', $id)
            ->with(['templates' => function ($query) {
                $query->where('status', 1)->orderBy('sort')->orderByDesc('id');
            }])
            ->first();

        return response()->json($album);
    }
}
