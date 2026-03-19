<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflow;
use App\Models\Order;
use Yansongda\Pay\Pay;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'filled|integer|min:1|max:200',
        ], [], [
            'page' => '页码',
            'limit' => '每页条数',
        ]);

        $limit = $request->input('limit', 20);

        $query = Workflow::query()->where('organization_id', 1)->where('status', 2)->where('list_status', 2);

        $workflows = $query->simplePaginate($limit);

        return response()->json($workflows);
    }

    public function payment(Request $request)
    {
        $request->validate([
            'workflow_id' => 'required|integer',
            'device_id' => 'required|string'
        ], [], [
            'id' => '商品 id',
            'device_id' => '设备 id'
        ]);

        $workflow_id = $request->input('workflow_id');

        $device_id = $request->input('device_id');

        $user = $request->user();

        $workflow = Workflow::query()->where('id', $workflow_id)->where('status', 2)->where('list_status', 2)->first();
        if (!$workflow) {
            return response()->json(['message' => '课程不存在或已下架'], 403);
        }

        if (!$workflow['price']) {
            return response()->json(['message' => '课程未定价,请联系管理员'], 403);
        }

        // 先看看是否有用户的订单未使用
        if (Order::query()->where('status', 2)->where('order_status', 2)->where('device_id', $device_id)->exists()) {
            return response()->json(['message' => '用户待体验,请等待用户体验完毕再购买', 'type' => 'confirm'], 403);
        }

        if (Order::query()->where('status', 2)->where('order_status', 3)->where('device_id', $device_id)->exists()) {
            return response()->json(['message' => '用户体验中,请等待用户体验完毕再购买', 'type' => 'confirm'], 403);
        }

        // 先查询是否有待支付的订单
        $order = Order::query()->where('workflow_id', $workflow_id)->where('device_id', $device_id)->where('status', 1)->first();

        if ($order && $order->user_id != $user->id) {
            return response()->json(['message' => '前边用户未付款，请稍等', 'type' => 'confirm'], 403);
        }

        if ($order) {
            $out_trade_no = $order->number . '-' . rand(1000000, 9999999);
        } else {
            $order = Order::create([
                'user_id' => $user->id,
                'device_id' => $device_id,
                'workflow_id' => $workflow_id,
                'name' => $workflow->name,
                'total_amount' => $workflow->price,
                'pay_amount' => $workflow->price,
                'payment_type' => 1,
                'status' => 1,
            ]);
            $out_trade_no = $order->number;
        }

        $pay = Pay::wechat(config('pay'))->mini([
            'out_trade_no' => $out_trade_no,
            'description' => substr($order['name'], 0, 48),
            'amount' => [
                'total' => $order['pay_amount'] * 100,
                'currency' => 'CNY',
            ],
            'payer' => [
                'openid' => $user->wxmini_openid,
            ],
            'attach' => 'workflows'
        ]);

        // 将订单 id 带过来
        $pay['order_id'] = $order->id;

        return response()->json($pay);
    }

    public function paymentStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ], [], [
            'id' => '商品 id'
        ]);

        $user = $request->user();

        $order = Order::query()->where('id', $request->id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['message' => '订单不存在'], 403);
        }

        $isPaid = $order->status == 2 ? true : false;

        return response()->json(['isPaid' => $isPaid, 'status' => $order->status, 'status_str' => $order->status_str]);
    }

    public function goodsOrders(Request $request)
    {
        $request->validate([
            'limit' => 'required|integer',
            'status' => 'filled|in:0,1,2,3'
        ], [], [
            'page_size' => '单页显示条数',
            'status' => '订单状态'
        ]);

        $limit = $request->limit;

        $user = $request->user();

        $query = Order::query()->where('user_id', $user->id);

        if (isset($request['status'])) {
            $query->where('status', $request['status']);
        }

        $orders = $query->with(['workflow:id,name,price,cover'])
            ->select(['id', 'user_id', 'number', 'workflow_id', 'name', 'total_amount', 'pay_amount', 'payment_type', 'paid_at', 'status', 'order_status', 'user_refund_status', 'user_refund_reason', 'refund_reject_reason'])
            ->orderBy('id', 'desc')
            ->simplePaginate($limit);

        return response()->json($orders);
    }
}
