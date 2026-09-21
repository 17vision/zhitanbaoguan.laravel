<?php

namespace App\Services;

use App\Models\User;
use App\Models\VipOrder;
use App\Utils\WeChatVirtualPay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VipVirtualOrderService
{
    public function __construct(private WeChatVirtualPay $virtualPay)
    {
    }

    /**
     * 查微信订单并在已支付时补发货
     */
    public function trySyncPaidOrder(VipOrder $order): bool
    {
        $user = User::query()->where('id', $order->user_id)->first();
        if (!$user || !$user->wxmini_openid) {
            return false;
        }

        $outTradeNo = (string) (($order->trade_info['virtual_out_trade_no'] ?? null) ?: $order->number);
        $result = $this->virtualPay->queryOrder(
            (string) $user->wxmini_openid,
            $outTradeNo,
            (int) $order->env
        );

        if (!$result['success']) {
            return false;
        }

        $wxOrder = $result['order'] ?? [];
        $status = (int) ($wxOrder['status'] ?? -1);

        // 2 待发货 / 3 发货中 / 4 已发货
        if (in_array($status, [2, 3, 4], true)) {
            $transactionId = (string) ($wxOrder['wxpay_order_id'] ?? ($wxOrder['wx_order_id'] ?? ''));
            $paidTime = $wxOrder['paid_time'] ?? null;
            $this->markPaidAndDeliver($order, $transactionId, $paidTime, [
                'query_order' => $wxOrder,
            ]);

            return true;
        }

        if ($status === 6 && (int) $order->status === 1) {
            $order->update([
                'status' => 0,
                'closed_at' => Carbon::now()->toDateTimeString(),
            ]);
        }

        if (in_array($status, [5, 8], true)) {
            Log::channel('pay')->info('virtualPayQueryRefunded', [
                'order_id' => $order->id,
                'wx_status' => $status,
            ]);
        }

        return false;
    }

    public function markPaidAndDeliver(VipOrder $order, string $transactionId, $paidTime, array $tradeExtra): void
    {
        DB::transaction(function () use ($order, $transactionId, $paidTime, $tradeExtra) {
            $order = VipOrder::query()->where('id', $order->id)->lockForUpdate()->first();
            if (!$order) {
                return;
            }

            $tradeInfo = $order->trade_info ?? [];
            foreach ($tradeExtra as $key => $value) {
                $tradeInfo[$key] = $value;
            }

            if (is_numeric($paidTime) && (int) $paidTime > 0) {
                $paidAt = Carbon::createFromTimestamp((int) $paidTime)->toDateTimeString();
            } elseif (is_string($paidTime) && $paidTime !== '') {
                $paidAt = Carbon::parse($paidTime)->toDateTimeString();
            } else {
                $paidAt = Carbon::now()->toDateTimeString();
            }

            if ((int) $order->status < 2) {
                $order->update([
                    'status' => 2,
                    'payment_number' => $transactionId ?: $order->payment_number,
                    'paid_at' => $paidAt,
                    'trade_info' => $tradeInfo,
                ]);
                $order->refresh();
            } else {
                $order->update([
                    'payment_number' => $transactionId ?: $order->payment_number,
                    'trade_info' => $tradeInfo,
                ]);
                $order->refresh();
            }

            if ((int) $order->goods_delivered === 1) {
                return;
            }

            $order->grantUserVipRights();
            $order->update(['goods_delivered' => 1]);

            Log::channel('pay-notify')->info('virtualPayDelivered', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
            ]);
        });
    }

    public function findOrderByOutTradeNo(string $outTradeNo): ?VipOrder
    {
        $bizNumber = explode('-', $outTradeNo)[0];

        $order = VipOrder::query()
            ->where('payment_channel', VipOrder::PAYMENT_CHANNEL_VIRTUAL)
            ->where('number', $bizNumber)
            ->first();

        if ($order) {
            return $order;
        }

        return VipOrder::query()
            ->where('payment_channel', VipOrder::PAYMENT_CHANNEL_VIRTUAL)
            ->where('trade_info', 'like', '%' . addcslashes($outTradeNo, '%_\\') . '%')
            ->orderByDesc('id')
            ->first();
    }
}
