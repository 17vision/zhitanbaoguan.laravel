<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BehaviorStatistic;
use App\Models\UserReceive;
use App\Models\Venue;
use App\Models\VipUser;
use Illuminate\Http\Request;

class BehaviorStatisticsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer',
            'type' => 'required|integer|in:1,2',
            'target_id' => 'nullable|integer',
        ], [], [
            'venue_id' => '场馆 id',
            'type' => '类型',
            'target_id' => '目标ID',
        ]);

        $type = (int) $request->input('type');
        $venue_id = (int) $request->input('venue_id');
        $user = $request->user();

        $venue = Venue::query()->where('id', $venue_id)->first();
        if (!$venue) {
            return response()->json(['message' => '场馆不存在'], 403);
        }

        if ($type === 2) {
            if (!$user) {
                return response()->json(['message' => '用户未登录'], 401);
            }

            $vipUser = VipUser::query()
                ->where('user_id', $user->id)
                ->where('venue_id', $venue_id)
                ->first();

            $isVipValid = $vipUser && $vipUser->expired_at && $vipUser->expired_at->isFuture();

            // VIP 不在有效期内：消耗当天领取的讲解次数
            if (!$isVipValid) {
                UserReceive::query()
                    ->where('user_id', $user->id)
                    ->where('venue_id', $venue_id)
                    ->whereDate('date', now()->toDateString())
                    ->where('explain_count', '>', 0)
                    ->decrement('explain_count');
            }
        }

        BehaviorStatistic::create([
            'organization_id' => $venue->organization_id,
            'venue_id' => $venue->id,
            'user_id' => optional($user)->id,
            'date' => now()->toDateString(),
            'type' => $type,
            'target_id' => $request->input('target_id'),
        ]);

        return response()->json(['message' => 'success']);
    }
}
