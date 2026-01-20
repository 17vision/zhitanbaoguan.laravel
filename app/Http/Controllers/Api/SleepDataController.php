<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SleepData;
use App\Models\SleepDataBean;

class SleepDataController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|integer|exists:users,id',
            'start_at' => 'required|date_format:Y-m-d H:i:s',
            'end_at' => 'required|date_format:Y-m-d H:i:s|after:start_at'
        ], [], [
            'type' => '类型',
            'start_at' => '开始时间',
            'end_at' => '结束时间',
        ]);

        $data = $request->only(['type', 'start_at', 'end_at']);

        $user = $request->user();

        $now = Carbon::now();

        // 以晚上 8 点开始为睡觉分界点
        $date = $now->hour < 20 ? $now->toDateString() : $now->addDays(1)->toDateString();

        // 先搞出 bean
        $sleepDataBean = SleepDataBean::query()->where('user_id', $user->id)->where('date', $date)->first();
        if (!$sleepDataBean) {
            $sleepDataBean = SleepDataBean::create([
                'user_id' => $user->id,
                'date' => $date,
                'start_at' => $data['start_at']
            ]);
        }

        $data['user_id'] = $user->id;
        $data['sleep_data_bean_id'] = $sleepDataBean['id'];

        SleepData::create($data);

        $sleepDatas = SleepData::query()->where('user_id', $user->id)->where('sleep_data_bean_id', $sleepDataBean['id'])->get()->toArray();

        $start_at = '';
        $end_at = '';
        $deep_sleep_count = 0;
        $light_sleep_count = 0;
        $rapid_eye_movement_count = 0;
        $deep_sleep_total = 0;
        $light_sleep_total = 0;
        $rapid_eye_movement_total = 0;
        $wake_count = 0;
        $wake_duration = 0;

        foreach ($sleepDatas as $sleepData) {
            $type = $sleepData['type'];
            if (\in_array($type, [1, 2, 3])) {
                if (!$start_at || Carbon::parse($sleepData['start_at'])->lt(Carbon::parse($start_at))) {
                    $start_at = $sleepData['start_at'];
                }

                if (!$end_at || Carbon::parse($sleepData['end_at'])->gt(Carbon::parse($end_at))) {
                    $end_at = $sleepData['end_at'];
                }
            }

            if ($type == 1) {
                $deep_sleep_count++;
                $deep_sleep_total += (Carbon::parse($sleepData['end_at'])->diffInSeconds($sleepData['start_at']));
            }

            if ($type == 2) {
                $light_sleep_count++;
                $light_sleep_total += (Carbon::parse($sleepData['end_at'])->diffInSeconds($sleepData['start_at']));
            }

            if ($type == 3) {
                $rapid_eye_movement_count++;
                $rapid_eye_movement_total += (Carbon::parse($sleepData['end_at'])->diffInSeconds($sleepData['start_at']));
            }

            if ($type == 4) {
                $wake_count++;
                $wake_duration += (Carbon::parse($sleepData['end_at'])->diffInSeconds($sleepData['start_at']));
            }
        }

        $sleepDataBean->update([
            'deep_sleep_count' => $deep_sleep_count,
            'light_sleep_count' => $light_sleep_count,
            'rapid_eye_movement_count' => $rapid_eye_movement_count,
            'start_at' => $start_at,
            'end_at' => $end_at,
            'deep_sleep_total' => $deep_sleep_total,
            'light_sleep_total' => $light_sleep_total,
            'rapid_eye_movement_total' => $rapid_eye_movement_total,
            'wake_count' => $wake_count,
            'wake_duration' => $wake_duration
        ]);
        return response()->json($sleepDataBean);
    }
}
