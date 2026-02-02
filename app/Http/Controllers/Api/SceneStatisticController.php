<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SceneStatistic;
use Illuminate\Http\Request;

class SceneStatisticController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'scene_id' => 'required|integer|min:1|exists:scenes,id',
            'type' => 'required|in:1,2,3,4',
            'duration' => 'required|integer|min:1'
        ],[],[
            'scene_id' => '场景 id',
            'type' => '类型',
            'duration' => '时长'
        ]);

        $user = $request->user();

        $data = $request->only(['scene_id', 'type', 'duration']);

        $data['user_id'] = $user->id;

        $sceneStatistic = SceneStatistic::create($data);

        return response()->json($sceneStatistic);
    }
}
