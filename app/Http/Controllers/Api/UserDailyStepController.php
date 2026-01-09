<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDailyStep;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserDailyStepController extends Controller
{
 public function store(Request $request)
    {
        $request->validate([
            'steps' => 'filled|integer|min:0',
            'calories' => 'filled|integer|min:0',
            'distance' => 'filled|numeric|min:0|max:100'
        ], [], [
            'steps' => '步数',
            'calories' => '卡路里',
            'distance' => '距离',
        ]);

        $data = $request->only(['steps', 'calories', 'distance']);

        $user = $request->user();

        if (empty($data)) {
            return response()->json(['message' => '请传入身体指标数据'], 403);
        }

        $data['user_id'] = $user->id;
        $data['date'] = Carbon::now()->toDateString();

        $userDailyStep = UserDailyStep::query()->where('user_id', $data['user_id'])->where('date', $data['date'])->first();

        if ($userDailyStep) {
            $userDailyStep->update($data);
        } else {
            $userDailyStep = UserDailyStep::create($data);
        }
        return response()->json($userDailyStep);
    }
}
