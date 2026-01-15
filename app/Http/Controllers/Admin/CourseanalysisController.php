<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\CourseCollect;
use App\Models\CourseLike;
use App\Models\CourseMessage;
use App\Models\CourseStatistics;

class CourseanalysisController extends Controller
{
    // 个人课程基本数据
    public function basic(Request $request)
    {
        $today     = Carbon::now()->startOfDay();
        $yesterday = $today->copy()->subDay()->startOfDay();

        $user = $request->user();

        // 喜欢
        $likeCmp = CourseLike::where('user_id', $user->id)->whereBetween('created_at', [$yesterday, $today->copy()->endOfDay()])
            ->selectRaw('
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as ytd,
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today
            ', [$yesterday, $today, $today, $today->copy()->addDay()])
            ->first();

        // 收藏
        $collectCmp = CourseCollect::where('user_id', $user->id)->whereBetween('created_at', [$yesterday, $today->copy()->endOfDay()])
            ->selectRaw('
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as ytd,
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today
            ', [$yesterday, $today, $today, $today->copy()->addDay()])
            ->first();

        // 消息（主贴 + 回复）
        $msgCmp = CourseMessage::where('user_id', $user->id)->whereBetween('created_at', [$yesterday, $today->copy()->endOfDay()])
            ->selectRaw('
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as ytd_count,
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today_count,
                COALESCE(SUM(CASE WHEN created_at >= ? AND created_at < ? THEN reply_nums ELSE 0 END), 0) as ytd_reply,
                COALESCE(SUM(CASE WHEN created_at >= ? AND created_at < ? THEN reply_nums ELSE 0 END), 0) as today_reply
            ', [
                $yesterday,
                $today,
                $today,
                $today->copy()->addDay(),
                $yesterday,
                $today,
                $today,
                $today->copy()->addDay(),
            ])
            ->first();

        $like_count = CourseLike::query()->where('user_id', $user->id)->count();
        $collect_count = CourseCollect::query()->where('user_id', $user->id)->count();
        $message_count = CourseMessage::query()->where('user_id', $user->id)->count();

        $data = [
            'like_count' => (int)$like_count,
            'collect_count' => (int)$collect_count,
            'message_count' => (int)$message_count,

            // 对比（今日 - 昨日）
            'like_count_compare'    => (int) ($likeCmp->today ?? 0)   - (int) ($likeCmp->ytd ?? 0),
            'collect_count_compare' => (int) ($collectCmp->today ?? 0) - (int) ($collectCmp->ytd ?? 0),
            'message_count_compare' => (int) ($msgCmp->today_count + $msgCmp->today_reply) -
                (int) ($msgCmp->ytd_count + $msgCmp->ytd_reply),
        ];

        return response()->json($data);
    }

    public function view(Request $request)
    {
        $request->validate([
            'title' => 'filled|string',
            'type' => 'required|in:1,2,3'
        ], [], [
            'title' => '课程名称',
            'type' => '类型'
        ]);

        $title = $request->input('title');
        
        $type = $request->input('type');

        $user = $request->user();

        $query = CourseStatistics::query()->with(['course:id,title'])->where('user_id', $user->id);
        if ($title) {
            $query->whereHas('course', function ($query) use ($title) {
                $query->where('title', 'like', '%' . $title . '%');
            });
        }

        // 日周月
        // 1 日的话取 20 天内的
        // 2 周的话取 2 个月内的
        // 3 月的话取 6 个月内的

        if ($type == 1) {
            $endDate = Carbon::now();
            $startDay = $endDate->clone()->addDays(-20);
            $query->whereBetween('date', [$startDay->startOfDay()->toDateString(), $endDate->endOfDay()->toDateString()]);
            $query->selectRaw('date, COUNT(*) as count');
            $query->groupBy('date')->orderByDesc('date');
            $courseStatistics = $query->get();
        } elseif ($type == 2) {
            $endDate = Carbon::now();
            $startDay = $endDate->clone()->addMonths(-2);
            $query->whereBetween('date', [$startDay->startOfDay()->toDateString(), $endDate->endOfDay()->toDateString()]);
            $query->selectRaw('YEARWEEK(date, 1) as week, COUNT(*) as count');
            $query->groupBy('week')->orderByDesc('week');
            $courseStatistics = $query->get();
        } elseif ($type == 3) {
            $endDate = Carbon::now();
            $startDay = $endDate->clone()->addMonths(-6);
            $query->whereBetween('date', [$startDay->startOfDay()->toDateString(), $endDate->endOfDay()->toDateString()]);

            // $query->selectRaw('course_id, CONCAT(YEAR(created_at), LPAD(MONTH(created_at), 2, "0")) as month, COUNT(*) as count');
            // $query->groupBy(['course_id', 'month'])->orderByDesc('month');
    
            $query->selectRaw('CONCAT(YEAR(date), LPAD(MONTH(date), 2, "0")) as month, COUNT(*) as count');
            $query->groupBy('month')->orderByDesc('month');
            
            $courseStatistics = $query->get();
        }

        $courseStatistics = array_reverse($courseStatistics);
        return response()->json($courseStatistics);
    }
}
