<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserBodyMetric;

class UserBodyMetricController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'height' => 'filled|numeric|min:0',
            'weight' => 'filled|numeric|min:0',
            'age' => 'filled|integer|min:1|max:132',
            'body_fat_pct' => 'filled|numeric|min:10|max:40',
        ], [], [
            'height' => '身高',
            'weight' => '体重',
            'age' => '年龄',
            'body_fat_pct' => '体质率',
        ]);

        $data = $request->only(['height', 'weight', 'age', 'body_fat_pct']);

        $user = $request->user();

        if (empty($data)) {
            return response()->json(['message' => '请传入身体指标数据'], 403);
        }

        $data['user_id'] = $user->id;

        $userBodyMetric = UserBodyMetric::create($data);

        return response()->json($userBodyMetric);
    }
}
