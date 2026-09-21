<?php

namespace App\Console\Commands;

use App\Models\VipOrder;
use App\Services\VipVirtualOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckVirtualVipOrder extends Command
{
    protected $signature = 'app:check-virtual-vip-order';

    protected $description = '虚拟支付 VIP 订单：查单补发货 / 超时关单';

    public function handle(VipVirtualOrderService $orderService)
    {
        $queryAfter = (int) config('virtual_pay.query_after_minutes', 2);
        $closeAfter = (int) config('virtual_pay.close_after_minutes', 30);

        $pendingOrders = VipOrder::query()
            ->where('payment_channel', VipOrder::PAYMENT_CHANNEL_VIRTUAL)
            ->where('status', 1)
            ->where('goods_delivered', 0)
            ->where('created_at', '<', now()->subMinutes($queryAfter))
            ->orderBy('id')
            ->limit(100)
            ->get();

        $synced = 0;
        foreach ($pendingOrders as $order) {
            try {
                if ($orderService->trySyncPaidOrder($order)) {
                    $synced++;
                }
            } catch (\Throwable $e) {
                Log::channel('cron')->error('CheckVirtualVipOrder sync fail', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $closed = VipOrder::query()
            ->where('payment_channel', VipOrder::PAYMENT_CHANNEL_VIRTUAL)
            ->where('status', 1)
            ->where('goods_delivered', 0)
            ->where('created_at', '<', now()->subMinutes($closeAfter))
            ->update([
                'status' => 0,
                'closed_at' => now()->toDateTimeString(),
            ]);

        $paidNotDelivered = VipOrder::query()
            ->where('payment_channel', VipOrder::PAYMENT_CHANNEL_VIRTUAL)
            ->where('status', 2)
            ->where('goods_delivered', 0)
            ->orderBy('id')
            ->limit(50)
            ->get();

        $reDelivered = 0;
        foreach ($paidNotDelivered as $order) {
            try {
                $order->grantUserVipRights();
                $order->update(['goods_delivered' => 1]);
                $reDelivered++;
            } catch (\Throwable $e) {
                Log::channel('cron')->error('CheckVirtualVipOrder redeliver fail', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::channel('cron')->info("CheckVirtualVipOrder: synced={$synced}, closed={$closed}, reDelivered={$reDelivered}");
        $this->info("synced={$synced}, closed={$closed}, reDelivered={$reDelivered}");

        return self::SUCCESS;
    }
}
