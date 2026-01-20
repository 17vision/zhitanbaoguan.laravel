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
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer',
        ], [], [
            'limit' => '单页显示条数',
            'page' => '当前页',
        ]);

        $limit = $request->input('limit', 30);

        $query = Scene::query()->where('status', 1);

        $scenes = $query->orderByDesc('id')->with(['sceneCategory'])->simplePaginate($limit);

        return response()->json($scenes);
    }
}
