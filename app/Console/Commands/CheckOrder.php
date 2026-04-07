<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOrder extends Command
{
    protected $signature = 'app:check-order';

    protected $description = 'Command description';

    public function handle()
    {
        // 如果一分钟内,没支付,就关闭订单
        $count1 = Order::query()->where('status', 1)->where('created_at', '<', now()->subMinute())->update(['status' => 0]);

        // 如果用户在体验中,卡了超过 16 分钟,也关闭订单
        $count2 = Order::query()->where('status', 2)->where('order_status', 3)->where('created_at', '<', now()->subMinute(16))->update(['order_status' => 0]);

        Log::channel('cron')->info("CheckOrder: 关闭订单: $count1, $count2");
    }
}
