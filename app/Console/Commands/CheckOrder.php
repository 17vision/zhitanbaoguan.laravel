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
