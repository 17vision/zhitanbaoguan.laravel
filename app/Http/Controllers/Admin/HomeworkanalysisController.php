<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserExtend;
use App\Models\UserHomework;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HomeworkanalysisController extends Controller
{
    public function basic(Request $request)
    {
        $user = $request->user();

        // $is_manager = $user->hasRole('root') || $user->hasRole('manager');

        $roleName = UserExtend::query()->where('user_id', $user->id)->value('admin_role');

        $is_manager = $roleName == 'root' || $roleName == 'manager';

        $now = now();

        if ($is_manager) {
            // 全部的作业
            $total = UserHomework::query()->count();

            // 已完成的作业
            $finished = UserHomework::query()->whereNotNull('completed_at')->count();

            // 按时完成
            $ontime = UserHomework::query()->whereNotNull('completed_at')->whereRaw('completed_at <= end_at')->count();

            // 超时完成
            $timeout = UserHomework::query()->whereNotNull('completed_at')->whereRaw('completed_at > end_at')->count();

            // 待完成
            $pending = UserHomework::query()->whereNull('completed_at')->where('end_at', '>=', $now)->count();

            // 到时未完成
            $unfinished  = UserHomework::query()->whereNull('completed_at')->where('end_at', '<', $now)->count();
        } else {
            // 全部的作业
            $total = UserHomework::query()->where('user_id', $user->id)->count();

            // 已完成的作业
            $finished = UserHomework::query()->where('user_id', $user->id)->whereNotNull('completed_at')->count();

            // 按时完成
            $ontime = UserHomework::query()->where('user_id', $user->id)->whereNotNull('completed_at')->whereRaw('completed_at <= end_at')->count();

            // 超时完成
            $timeout = UserHomework::query()->where('user_id', $user->id)->whereNotNull('completed_at')->whereRaw('completed_at > end_at')->count();

            // 待完成
            $pending = UserHomework::query()->where('user_id', $user->id)->whereNull('completed_at')->where('end_at', '>=', $now)->count();

            // 到时未完成
            $unfinished  = UserHomework::query()->where('user_id', $user->id)->whereNull('completed_at')->where('end_at', '<', $now)->count();
        }

        $data = [
            'total' => $total,
            'finished' => $finished,
            'ontime_finished' => $ontime,
            'timeout_finished' => $timeout,
            'pending' => $pending,
            'unfinished' => $unfinished,
            'ontime_finished_rate' => round(1000 * $ontime / $finished) / 10 . '%',
            'finished_rate' => round(1000 * $finished / $total) / 10 . '%',
            'total_str' => '总数',
            'finished_str' => '已完成数',
            'ontime_finished_str' => '按时完成',
            'timeout_finished_str' => '超时完成',
            'ontime_finished_rate_str' => '按时完成率',
            'finished_rate_str' => '完成率',
            'pending_str' => '待完成',
            'unfinished_str' => '未完成',
        ];

        return response()->json($data);
    }

    public function view(Request $request)
    {
        // 完成作业排行
        $completed = UserHomework::query()->whereNotNull('completed_at')->selectRaw('homework_id,COUNT(*) as count')->groupBy('homework_id')->orderByDesc('count')->get()->toArray();

        $maps = Arr::pluck($completed, 'count', 'homework_id');

        $total = UserHomework::query()->whereNotNull('completed_at')->with(['homework'])->selectRaw('homework_id,COUNT(*) as count')->groupBy('homework_id')->orderByDesc('count')->get()->toArray();

        $homework_rates = [];
        foreach($total as $item) {
            if (isset($maps[$item['homework_id']])) {
                $item['finished'] = $maps[$item['homework_id']];
                $item['total'] = $item['count'];

                $homework_rates[] = [
                    'finished' => $maps[$item['homework_id']],
                    'total' => $item['count'],
                    'rate' =>  round(1000 * $maps[$item['homework_id']]/ $item['count'])/10 . '%',
                    'homework_name' => $item['homework']['title'] ?? '',
                ];
            } else {
                $homework_rates[] = [
                    'finished' => 0,
                    'total' => $item['count'],
                    'rate' => '0%',
                    'homework_name' => $item['homework']['title'] ?? '',
                ];
            }
        }

        $rankings = UserHomework::query()->whereNotNull('completed_at')->with(['user'])->selectRaw('user_id,COUNT(*) as count')->groupBy('user_id')->orderByDesc('count')->get()->toArray();

        foreach($rankings as &$ranking) {
            $ranking['nickname'] = $ranking['user']['nickname'] ?? '';
            unset($ranking['user']);
        }

        $data = [
            'homework_rates' => $homework_rates,
            'user_rankings' => $rankings
        ];

        return response()->json($data);
    }
}
