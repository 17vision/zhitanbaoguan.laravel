<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserExtend;
use App\Models\UserHomework;
use Illuminate\Http\Request;

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
            'total_str' => '总',
            'finished_str' => '已完成',
            'ontime_finished_str' => '按时完成',
            'timeout_finished_str' => '超时完成',
            'pending_str' => '待完成',
            'unfinished_str' => '未完成',
        ];

        return response()->json($data);
    }

    public function view(Request $request)
    {
        return response()->json($request->all());
    }
}
