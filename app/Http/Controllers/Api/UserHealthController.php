<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserHealth;
use Illuminate\Http\Request;

class UserHealthController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'heart_rate' => 'sometimes|required|integer|min:30|max:250',
            'blood_oxygen' => 'sometimes|required|numeric|min:0|max:100',
            'systolic' => 'sometimes|required|numeric|min:50|max:300',
            'diastolic' => 'sometimes|required|numeric|min:30|max:200',
        ], [], [
            'heart_rate' => '心率',
            'blood_oxygen' => '血氧',
            'systolic' => '收缩压',
            'diastolic' => '舒张压',
        ]);

        $data = $request->only(['heart_rate', 'blood_oxygen', 'systolic', 'diastolic']);

        $user = $request->user();

        if (empty($data)) {
            return response()->json(['message' => '请传入健康数据'], 403);
        }

        $userHealth = UserHealth::query()->where('user_id', $user->id)->first();

        $userHealth = UserHealth::create($data);
        
        return response()->json($userHealth);
    }
}
