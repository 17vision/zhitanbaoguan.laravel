<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCollect;
use App\Models\CourseLike;
use App\Models\CourseMessage;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function basicInfo()
    {
        $course = Course::query()
            ->where('status', 1)
            ->selectRaw('COUNT(*) as course_count, COALESCE(SUM(like_count),0) as like_count, COALESCE(SUM(collect_count),0) as collect_count, COALESCE(SUM(message_count),0) as message_count')
            ->first();

        $today     = Carbon::now()->startOfDay();
        $yesterday = $today->copy()->subDay()->startOfDay();

        // 课程
        $courseCmp = Course::whereBetween('created_at', [$yesterday, $today->copy()->endOfDay()])
            ->selectRaw('
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as ytd,
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today
            ', [$yesterday, $today, $today, $today->copy()->addDay()])
            ->first();

        // 喜欢
        $likeCmp = CourseLike::whereBetween('created_at', [$yesterday, $today->copy()->endOfDay()])
            ->selectRaw('
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as ytd,
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today
            ', [$yesterday, $today, $today, $today->copy()->addDay()])
            ->first();

        // 收藏
        $collectCmp = CourseCollect::whereBetween('created_at', [$yesterday, $today->copy()->endOfDay()])
            ->selectRaw('
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as ytd,
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today
            ', [$yesterday, $today, $today, $today->copy()->addDay()])
            ->first();

        // 消息（主贴 + 回复）
        $msgCmp = CourseMessage::whereBetween('created_at', [$yesterday, $today->copy()->endOfDay()])
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

        $data = [
            'course_count' => (int)$course['course_count'] ?? 0,
            'like_count' => (int)$course['like_count'] ?? 0,
            'collect_count' => (int)$course['collect_count'] ?? 0,
            'message_count' => (int)$course['message_count'] ?? 0,
            // 对比（今日 - 昨日）
            'course_count_compare'  => (int) ($courseCmp->today ?? 0) - (int) ($courseCmp->ytd ?? 0),
            'like_count_compare'    => (int) ($likeCmp->today ?? 0)   - (int) ($likeCmp->ytd ?? 0),
            'collect_count_compare' => (int) ($collectCmp->today ?? 0) - (int) ($collectCmp->ytd ?? 0),
            'message_count_compare' => (int) ($msgCmp->today_count + $msgCmp->today_reply) -
                (int) ($msgCmp->ytd_count + $msgCmp->ytd_reply),
        ];

        return response()->json($data);
    }

    public function singleData(Request $request)
    {
        $request->validate([
            'title' => 'filled|string'
        ],[],[
            'title' => '课程名称'
        ]);

        $title = $request->title;

        $query = Course::query()->where('status', 1);

        if ($title) {
            $query->where('title', 'like', '%' . $title . '%');
        }

        $course = $query->selectRaw('id, title, COALESCE(SUM(like_count),0) as like_count, COALESCE(SUM(collect_count),0) as collect_count, COALESCE(SUM(message_count),0) as message_count')
            ->groupBy(['id'])
            ->get();

        $course->transform(function ($item) {
            return [
                'id' => $item['id'],
                'title' => $item['title'],
                'like_count' => (int)$item['like_count'],
                'collect_count' => (int)$item['collect_count'],
                'message_count' => (int)$item['message_count'],
            ];
        });
    }
}
