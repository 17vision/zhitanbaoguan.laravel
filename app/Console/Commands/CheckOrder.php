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
        // 普通微信支付 VIP：超过 30 分钟未支付则关闭（虚拟支付由 app:check-virtual-vip-order 处理）
        $count = VipOrder::query()
            ->where('status', 1)
            ->where('payment_channel', VipOrder::PAYMENT_CHANNEL_WECHAT)
            ->where('created_at', '<', now()->subMinutes(30))
            ->update([
                'status' => 0,
                'closed_at' => now()->toDateTimeString(),
            ]);

        Log::channel('cron')->info("CheckOrder: 关闭vip订单:{$count}");
    }
}
