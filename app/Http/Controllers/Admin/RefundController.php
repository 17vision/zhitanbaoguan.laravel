<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'required|in:1,2,3,4',
            'limit' => 'filled|integer'
        ], [], [
            'status' => '订单状态',
            'limit' => '分页'
        ]);

        $status = $request->input('status');

        $limit = $request->input('limit', 30);

        $query = Order::query()->with(['workflow:id,name,price,cover', 'user:id,nickname,avatar,gender']);
        if ($status) {
            $query->where('user_refund_status', $status);
        }

        $orders = $query->orderByDesc('id')->paginate($limit);

        // 对数据进行过滤处理
        foreach ($orders as &$order) {
            if ($order['workflow']) {
                $order['workflow_name'] = $order['workflow']['name'];
                $order['workflow_price'] = $order['workflow']['price'];
                $order['workflow_cover'] = $order['workflow']['cover'];

                unset($order['workflow']);
            }

            if ($order['user']) {
                $order['user_nickname'] = $order['user']['nickname'];
                $order['user_avatar'] = $order['user']['avatar'];
                unset($order['user']);
            }
        }
        return response()->json($orders);
    }

    public function reject(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'reason' => 'required|string'
        ], [], [
            'order_id' => '订单 id',
            'reason' => '拒绝的理由'
        ]);

        $order_id = $request->order_id;
        $reason = $request->reason;

        $order = Order::query()->where('id', $order_id)->first();
        if (!$order) {
            return response()->json(['message' => '订单不存在'], 403);
        }


        if ($order->user_refund_status != 1) {
            return response()->json(['message' => '该订单未申请退款'], 403);
        }

        $result = $order->update([
            'user_refund_status' => 4,
            'refund_reject_reason' => $reason
        ]);

        return response()->json(['result' => $result]);
    }
}
