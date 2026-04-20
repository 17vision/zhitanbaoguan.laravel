<?php

namespace App\Http\Controllers\Api;

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
            'parent_id' => 'filled|integer|exists:places,id'
        ], [], [
            'limit' => '单页显示条数',
            'venue_id' => '场馆 id',
            'parent_id' => '父点位 id',
        ]);

        $limit = $request->input('limit', 30);

        $venue_id = $request->input('venue_id');

        $parent_id = $request->input('parent_id');

        $query = Place::with(['introductions', 'medias'])->where('venue_id', $venue_id)->where('status', 1)->orderBy('sort');

        if ($parent_id) {
            $query->where('parent_id', $parent_id);
        } else {
            $query->whereNull('parent_id');
        }

        $places = $query->simplePaginate($limit);
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
}
