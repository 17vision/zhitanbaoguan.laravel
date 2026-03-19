<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Workflow;
use App\Models\Order;

class OrderController extends Controller
{
    // unity 获取要播放的订单
    public function paidOrders(Request $request)
    {
        $request->validate([
            'device_id' => 'required|integer'
        ], [], [
            'device_id' => '设备 id'
        ]);

        $device_id = $request->input('device_id');

        $order = Order::query()->where('device_id', $device_id)->where('status', 2)->where('order_status', 2)
                    ->whereNull('refund_status')->with(['workflow:id,name,price,cover', 'user:id,nickname'])->orderBy('id', 'asc')->first();

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
                'workflow_id' => $order->workflow_id,
                'workflow_name' => $order->workflow['name'],
                'workflow_price' => $order->workflow['price'],
                'workflow_cover' => $order->workflow['cover'],
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
            'user_id' => 'required|integer'
        ], [], [
            'order_id' => '订单 id',
            'user_id' => '用户 id'
        ]);

        $order_id = $request->order_id;
        $user_id = $request->user_id;

        Log::channel('unity')->info('updateOrders', ['order_id' => $order_id, 'user_id' => $user_id, 'sign' => 1]);

        $order = Order::query()->where('id', $order_id)->first();
        if (!$order) {
            return response()->json(['message' => '订单不存在'], 403);
        }

        if ($order->status != 2) {
            return response()->json(['message' => '订单不是已支付的状态'], 403);
        }

        if ($order->user_id != $user_id) {
            return response()->json(['message' => '订单错误'], 403);
        }

        // 从待体验到体验中
        if ($order->order_status == 2) {
            $result = $order->update(['order_status' => 3]);
        } else if ($order->order_status == 3) {
            $result = $order->update(['order_status' => 4]);
        } else {
            return response()->json(['message' => '状态错误'], 403);
        }

        Log::channel('unity')->info('updateOrders', ['order_id' => $order_id, 'user_id' => $user_id, 'sign' => 2, 'result' => $result]);

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
