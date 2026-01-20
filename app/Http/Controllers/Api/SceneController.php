<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Scene;
class SceneController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|in:1,2,3,4',
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
        ], [], [
            'type' => '类型',
            'limit' => '单页显示条数',
            'page' => '当前页',
        ]);

        $type = $request->input('type');

        $limit = $request->input('limit', 30);

        $query = Scene::query()->where('status', 1)->where('type', 'like', "%$type%");

        $scenes = $query->orderByDesc('id')->with(['sceneCategory'])->simplePaginate($limit);

        return response()->json($scenes);
    }
}
