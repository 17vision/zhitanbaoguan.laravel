<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\VipOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOrder extends Command
{
    protected $signature = 'app:check-order';

    protected $description = '检查订单,关闭卡着的订单';

    public function handle()
    {
        // 如果一分钟内,没支付,就关闭订单
        $count1 = Order::query()->where('status', 1)->where('created_at', '<', now()->subMinute())->update(['status' => 0]);

        // 如果用户在体验中,卡了超过 16 分钟,也关闭订单
        $count2 = Order::query()->where('status', 2)->where('order_status', 3)->where('created_at', '<', now()->subMinute(16))->update(['order_status' => 0]);

        // VIP 订单超过 30 分钟未支付，关闭订单
        $count3 = VipOrder::query()
            ->where('status', 1)
            ->where('created_at', '<', now()->subMinutes(30))
            ->update([
                'status' => 0,
                'closed_at' => now()->toDateTimeString(),
            ]);

        Log::channel('cron')->info("CheckOrder: 关闭workflows订单:{$count1}, {$count2} | 关闭vip订单:{$count3}");
    }
}
