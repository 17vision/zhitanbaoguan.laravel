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
            'distance' => 'filled|numeric|min:0|max:100',
            'date' => 'filled|date',
            'hour' => 'required_with:date|integer|min:0|max:23'
        ], [], [
            'steps' => '步数',
            'calories' => '卡路里',
            'distance' => '距离',
            'date' => '天',
            'hour' => '小时'
        ]);

        $data = $request->only(['steps', 'calories', 'distance']);

        $date = $request->input('date', Carbon::now()->toDateString());

        $hour = $request->input('hour', Carbon::now()->hour);

        $user = $request->user();

        if (empty($data)) {
            return response()->json(['message' => '请传入身体指标数据'], 403);
        }

        $data['user_id'] = $user->id;
        $data['date'] = $date;
        $data['hour'] = $hour;

        $userDailyStep = UserDailyStep::query()
                ->where('user_id', $data['user_id'])
                ->where('date', $data['date'])
                ->where('hour', $data['hour'])
                ->first();

        if ($userDailyStep) {
            $userDailyStep->update($data);
        } else {
            $userDailyStep = UserDailyStep::create($data);
        }
        return response()->json($userDailyStep);
    }
}
