<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VipOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yansongda\Pay\Pay;

class VipOrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'venue_id' => 'nullable|integer|exists:venues,id',
            'limit' => 'filled|integer|min:1',
            'status' => 'filled|in:0,1,2,3',
        ], [], [
            'venue_id' => '场馆 id',
            'limit' => '单页显示条数',
            'status' => '订单状态',
        ]);

        $limit = $request->input('limit', 30);
        $user = $request->user();

        $query = VipOrder::query()->with([
            'vipPackage',
            'user:id,nickname,avatar,gender',
        ])->orderByDesc('id');

        $query->where('user_id', $user->id);
        if ($venue_id = (int) $request->input('venue_id')) {
            $query->where('venue_id', $venue_id);
        }

        if (isset($request['status'])) {
            $query->where('status', $request['status']);
        }

        $orders = $query->simplePaginate($limit);

        return response()->json($orders);
    }

    public function quickPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'nullable|integer|exists:vip_orders,id',
            'venue_id' => 'required_without:order_id|integer|exists:venues,id',
            'quick_type' => 'nullable|integer|in:1',
        ], [], [
            'order_id' => '订单 id',
            'venue_id' => '场馆 id',
            'quick_type' => '快捷购买类型',
        ]);

        $user = $request->user();

        if (!$user->wxmini_openid) {
            return response()->json(['message' => '未绑定微信小程序，无法支付'], 403);
        }

        $description = '快捷购买';
        $order = null;
        $out_trade_no = null;

        if ($request->filled('order_id')) {
            $order = VipOrder::query()
                ->where('id', $request->input('order_id'))
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json(['message' => '订单不存在'], 403);
            }

            if ((int) $order->status !== 1) {
                return response()->json(['message' => '订单状态不允许支付'], 403);
            }

            $order_number = explode('-', $order->number)[0];
            $out_trade_no = $order_number . '-' . rand(1000000, 9999999);
        } else {
            $venue_id = $request->input('venue_id');
            $quick_type = (int) ($request->input('quick_type', 1));

            $venue = Venue::query()->where('id', $venue_id)->first();
            if (!$venue) {
                return response()->json(['message' => '场馆不存在'], 403);
            }

            if ($quick_type === 1) {
//                $total_amount = '9.90';
//                $pay_amount = '9.90';
                $total_amount = '0.01';
                $pay_amount = '0.01';
                $combine_count = 3; // 默认3次合成照片次数
                $vip_duration = 86400; // 默认1天会员有效期
                $client_type = 1;
            } else {
                return response()->json(['message' => '不支持的快捷购买类型'], 403);
            }

            DB::beginTransaction();
            try {
                $order = VipOrder::create([
                    'organization_id' => $venue->organization_id,
                    'venue_id' => $venue_id,
                    'user_id' => $user->id,
                    'vip_package_id' => null,
                    'combine_count' => $combine_count,
                    'vip_duration' => $vip_duration,
                    'total_amount' => $total_amount,
                    'pay_amount' => $pay_amount,
                    'payment_type' => 1,
                    'status' => 1,
                    'client_type' => $client_type,
                ]);
                $out_trade_no = $order->number;
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::channel('error')->error('vip快捷订单创建失败: ', ['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()]);
                return response()->json(['message' => '创建订单失败'], 403);
            }
        }

        $payParams = [
            'out_trade_no' => $out_trade_no,
            'description' => $description,
            'amount' => [
                'total' => (int) bcmul($order->pay_amount, '100', 0),
                'currency' => 'CNY',
            ],
            'attach' => 'vip',
        ];

        $payData = Pay::wechat(config('pay'))->mini(array_merge($payParams, [
            'payer' => [
                'openid' => $user->wxmini_openid,
            ],
        ]));

        $payData['order_id'] = $order->id;
        return response()->json($payData);
    }

    public function paymentStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '订单 id',
        ]);

        $user = $request->user();

        $order = VipOrder::query()->with([
            'vipPackage',
            'user:id,nickname,avatar,gender',
        ])->where('id', $request->id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['message' => '订单不存在'], 403);
        }

        return response()->json($order);
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '订单 id',
        ]);

        $user = $request->user();

        $order = VipOrder::query()->where('id', $request->id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['message' => '订单不存在'], 403);
        }

        if ($order->status != 1) {
            return response()->json(['message' => '订单状态不允许取消'], 403);
        }

        $order->update(['status' => 0, 'closed_at' => Carbon::now()->toDateTimeString()]);

        return response()->json($order);
    }

    public function refund(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'reason' => 'required|string',
        ], [], [
            'order_id' => '订单 id',
            'reason' => '退款理由',
        ]);

        $user = $request->user();
        $order_id = $request->order_id;
        $reason = $request->reason;

        Log::channel('pay')->info('doVipRefund', ['order_id' => $order_id, 'user_id' => $user->id, 'sign' => 1]);

        $order = VipOrder::query()->where('id', $order_id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['message' => '订单不存在'], 403);
        }

        if (!$order->paid_at) {
            return response()->json(['message' => '订单未支付'], 403);
        }

        if ($order->status == 3 || ($order->status == 0 && $order->refund_status)) {
            return response()->json(['message' => '订单已退款或退款请求中'], 403);
        }

        if ($order->refund_status == 1) {
            return response()->json(['message' => '订单退款处理中'], 403);
        }

        if ($order->refund_status == 2) {
            return response()->json(['message' => '订单已退款'], 403);
        }

        if ($order->user_refund_status) {
            $array = ['', '退款申请中', '退款中', '已退款', '退款已驳回'];
            if (isset($array[$order->user_refund_status]) && $array[$order->user_refund_status]) {
                return response()->json(['message' => $array[$order->user_refund_status], 'type' => 'confirm'], 403);
            }

            if ($order->user_refund_status == 1) {
                return response()->json(['message' => '请联系管理员'], 403);
            }
        }

        $result = $order->update(['user_refund_status' => 1, 'user_refund_reason' => $reason]);

        Log::channel('pay')->info('doVipRefund', ['order_id' => $order_id, 'user_id' => $user->id, 'reason' => $reason, 'result' => $result, 'sign' => 2]);

        return response()->json(['result' => $result]);
    }
}
