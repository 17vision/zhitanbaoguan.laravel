<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yansongda\Pay\Pay;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'filled|in:1,2,3',
            'order_status' => 'filled|in:1,2,3',
            'limit' => 'filled|integer'
        ], [], [
            'status' => '订单状态',
            'order_status' => '体验状态',
            'limit' => '分页'
        ]);

        $status = $request->input('status');

        $order_status = $request->input('order_status');

        $limit = $request->input('limit', 30);

        $query = Order::query()->with(['workflow:id,name,price,cover', 'user:id,nickname,avatar,gender']);
        if ($status) {
            $query->where('status', $status);
        }

        if ($order_status) {
            $query->where('order_status', $order_status);
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

    public function refund(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer'
        ], [], [
            'order_id' => '订单 id'
        ]);

        $order_id = $request->order_id;

        $order = Order::query()->where('id', $order_id)->first();

        if ($order->refund_status == 1) {
            return response()->json(['message' => '退款中，请稍后'], 403);
        }

        if ($order->refund_status == 2) {
            // $order->update([
            //     'status' => 3,
            //     'order_status' => 5,
            // ]);
            return response()->json(['message' => '已退款,请联系管理员'], 403);
        }

        $data = [
            'transaction_id' => $order->payment_number,
            'out_refund_no' => $order->payment_number . $order->number,
            'amount' => [
                'refund' => (int) ($order->pay_amount * 100),
                'total' => (int) ($order->pay_amount * 100),
                'currency' => 'CNY',
            ],
            '_action' => 'mini', // 小程序退款
        ];

        $result = Pay::wechat(config('pay'))->refund($data)->toArray();

        if ($order->user_refund_status == 1) {
            $order->update(['refund_status' => 1, 'user_refund_status' => 2]);
        } else {
            $order->update(['refund_status' => 1]);
        }

        Log::channel('pay-refund')->info('refund', ['data' => $data, '$result' => $result]);
        return response()->json($result);
    }

    // 只关闭，不退款
    public function close(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer'
        ], [], [
            'order_id' => '订单 id'
        ]);

        $order_id = $request->order_id;

        $order = Order::query()->where('id', $order_id)->first();

        if ($order->status != 2) {
            return response()->json(['仅支持关闭已支付的订单'], 403);
        }

        if ($order->order_status != 3) {
            return response()->json(['仅支持关闭体验中的订单'], 403);
        }

        $result = $order->update([
            'status' => 0,
            'closed_at' => Carbon::now()->toDateTimeString()
        ]);

        return response()->json($result);
    }
}