<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderItem;

class OrderController extends Controller
{
    // unity 获取要播放的订单
    public function paidOrders(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string'
        ], [], [
            'device_id' => '设备 id'
        ]);

        Log::info('paidOrders request', ['device_id' => $request->device_id, 'all' => $request->all()]);

        $device_id = $request->input('device_id');

        $order = Order::query()->where('device_id', $device_id)->where('status', 2)->where('order_status', 2)
                    ->whereNull('refund_status')->with(['items.workflow:id,name,price,cover', 'user:id,nickname'])->orderBy('id', 'asc')->first();

        if ($order) {
            $data = [
                'order_id' => $order->id,
                'device_id' => $order->device_id,
                'pay_amount' => $order->pay_amount,
                'paid_at' => $order->paid_at,
                'status' => $order->status_str,
                'order_status' => $order->order_status_str,
                'user_refund_status' => $order->user_refund_status,
                'user_refund_reason' => $order->user_refund_reason,
                'refund_reject_reason' => $order->refund_reject_reason,
                'user_id' => $order->user_id,
                'user_nickname' => $order->user['nickname'],
                'items' => $order['items']
            ];
        } else {
            $data = null;
        }
        return response()->json($data);
    }

    // 更新订单状态
    public function updateOrders(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'user_id' => 'required|integer',
            'item_id' => 'required|integer',
            'is_end' => 'filled|boolean'
        ], [], [
            'order_id' => '订单 id',
            'user_id' => '用户 id',
            'item_id' => '子订单 id',
            'is_end' => '是否结束'
        ]);

        $order_id = $request->order_id;
        $user_id = $request->user_id;
        $item_id = $request->item_id;
        $is_end = $request->is_end;

        Log::channel('unity')->info('updateOrders', ['order_id' => $order_id, 'user_id' => $user_id, 'item_id' => $item_id, 'is_end' => $is_end]);

        $order = Order::query()->where('id', $order_id)->first();

        $game_over = false;
        if (!$order) {
            return response()->json(['message' => '订单不存在'], 403);
        }

        if ($order->status != 2) {
            return response()->json(['message' => '订单不是已支付的状态'], 403);
        }

        if ($order->user_id != $user_id) {
            return response()->json(['message' => '订单错误 1'], 403);
        }

        $orderItem = OrderItem::query()->where('id', $item_id)->first();
        if (!$orderItem) {
            return response()->json(['message' => '子订单不存在'], 403);
        }

        if ($orderItem['order_id'] != $order_id) {
            return response()->json(['message' => '订单错误 2'], 403);
        }

        $now = now();
        // 从待体验到体验中
        if ($order->order_status == 2) {
            $result = $order->update(['order_status' => 3, 'play_begin_at' => now()]);
            $orderItem->update(['play_begin_at' => $now]);
        } else if ($order->order_status == 3) {
            if (!$orderItem['play_begin_at']) {
                $result = $orderItem->update(['play_begin_at' => $now]);
            } elseif (!$orderItem['play_end_at']) {
                $result = $orderItem->update(['play_end_at' => $now]);
            }

            if ($is_end || OrderItem::query()->where('order_id', $order_id)->whereNull('play_end_at')->count() == 0) {
                $result = $order->update(['order_status' => 4, 'play_end_at' => $now]);

                $game_over = true;
            }
        } else {
            return response()->json(['message' => '状态错误'], 403);
        }

        Log::channel('unity')->info('updateOrders', ['order_id' => $order_id, 'user_id' => $user_id, 'result' => $result, 'game_over' => $game_over]);

        return response()->json(['result' => $result]);
    }

    // 用户发起退款
    public function refundOrders(Request $request) 
    {
        $request->validate([
            'order_id' => 'required|integer',
            'user_id' => 'required|integer',
            'reason' => 'required|string'
        ], [], [
            'order_id' => '订单 id',
            'user_id' => '用户 id',
            'reason' => '退款理由'
        ]);

        $order_id = $request->order_id;
        $user_id = $request->user_id;
        $reason = $request->reason;

        Log::channel('pay')->info('doRefund', ['order_id' => $order_id, 'user_id' => $user_id, 'sign' => 1]);

        $order = Order::query()->where('id', $order_id)->first();
        if (!$order) {
            return response()->json(['message' => '订单不存在'], 403);
        }

        if ($order->user_id != $user_id) {
            return response()->json(['message' => '订单错误'], 403);
        }

        if ($order->refund_status) {
            return response()->json(['message' => '订单已退款'], 403);
        }

        if (!$order->paid_at) {
            return response()->json(['message' => '订单未支付'], 403);
        }

        if ($order->status == 3 || ($order->status == 0 && $order->refund_status)) {
            return response()->json(['message' => '订单已退款或退款请求中'], 403);
        }

        if ($order->user_refund_status) {
            $array = [ '', '退款申请中', '退款中', '已退款', '退款已驳回'];
            if (isset($array[$order->user_refund_status]) && $array[$order->user_refund_status]) {
                return response()->json(['message' => $array[$order->user_refund_status], 'type' => 'confirm'], 403);
            }

            if ($order->user_refund_status == 1) {
                return response()->json(['message' => '请联系管理员'], 403);
            }
        }

        $result = $order->update(['user_refund_status' => 1, 'user_refund_reason' => $reason]);

        Log::channel('pay')->info('doRefund', ['order_id' => $order_id, 'user_id' => $user_id, 'reason' => $reason, 'result' => $result, 'sign' => 2]);

        return response()->json(['result' => $result]);
    }
}
