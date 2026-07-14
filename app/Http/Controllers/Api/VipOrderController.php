<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VipOrder;
use App\Models\VipPackage;
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
            'venue_id' => 'required|integer|exists:venues,id',
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

        $query->where('user_id', $user->id)->where('venue_id', $request->input('venue_id'));

        if (isset($request['status'])) {
            $query->where('status', $request['status']);
        }

        $orders = $query->simplePaginate($limit);

        return response()->json($orders);
    }

    public function payment(Request $request)
    {
        $request->validate([
            'vip_package_id' => 'required_without:vip_order_id|filled|integer|exists:vip_packages,id',
            'vip_order_id' => 'required_without:vip_package_id|filled|integer|exists:vip_orders,id',
            'client_type' => 'filled|in:1,2,3',
        ], [], [
            'vip_package_id' => '套餐 id',
            'vip_order_id' => '订单 id',
            'client_type' => '客户端类型',
        ]);

        $user = $request->user();
        $vip_package_id = $request->vip_package_id;
        $vip_order_id = $request->vip_order_id;
        $clientType = (int) ($request->client_type ?: 1);

        $order = null;
        if ($vip_order_id) {
            $order = VipOrder::query()
                ->where('user_id', $user->id)
                ->where('id', $vip_order_id)
                ->where('status', 1)
                ->first();

            if (!$order) {
                return response()->json(['message' => '待支付订单不存在'], 403);
            }

            if ((int) $order->client_type !== $clientType) {
                $order->update(['client_type' => $clientType]);
            }
            $vip_package_id = $order->vip_package_id;
        }

        $vipPackage = VipPackage::query()->where('id', $vip_package_id)->where('status', 1)->first();
        if (!$vipPackage) {
            return response()->json(['message' => '套餐不存在或已下架'], 403);
        }

        if ($vipPackage->price === null) {
            return response()->json(['message' => '套餐未定价,请联系管理员'], 403);
        }

        if ($vipPackage->is_only_once) {
            $alreadyBuy = VipOrder::query()
                ->where('user_id', $user->id)
                ->where('status', 2)
                ->where('vip_package_id', $vipPackage->id)
                ->exists();
            if ($alreadyBuy) {
                return response()->json(['message' => '该套餐尽可购买一次,请联系管理员'], 403);
            }
        }

        $pay_amount = $order ? $order->pay_amount : $vipPackage->price;

        if ($order) {
            $order_number = explode('-', $order->number)[0];
            $out_trade_no = $order_number . '-' . rand(1000000, 9999999);
        } else {
            DB::beginTransaction();
            try {
                $order = VipOrder::create([
                    'organization_id' => $vipPackage->organization_id,
                    'venue_id' => $vipPackage->venue_id,
                    'user_id' => $user->id,
                    'vip_package_id' => $vipPackage->id,
                    'combine_count' => $vipPackage->combine_count,
                    'chinese_explain' => $vipPackage->chinese_explain,
                    'multi_explain' => $vipPackage->multi_explain,
                    'total_amount' => $pay_amount,
                    'pay_amount' => $pay_amount,
                    'payment_type' => 1,
                    'status' => 1,
                    'client_type' => $clientType,
                ]);
                $out_trade_no = $order->number;
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::channel('error')->error('vip订单创建失败: ', ['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()]);
                return response()->json(['message' => '创建订单失败'], 403);
            }
        }

        if ($pay_amount == 0) {
            $order->update(['status' => 2, 'paid_at' => now()]);
            $order->grantUserVipRights();
            return response()->json(['message' => '购买成功', 'order_id' => $order->id]);
        }

        $payParams = [
            'out_trade_no' => $out_trade_no,
            'description' => mb_substr($vipPackage->package_name, 0, 48),
            'amount' => [
                'total' => (int) bcmul($order->pay_amount, '100', 0),
                'currency' => 'CNY',
            ],
            'attach' => 'vip',
        ];

        // 小程序支付
        if (!$user->wxmini_openid) {
            return response()->json(['message' => '未绑定微信小程序，无法支付'], 403);
        }

        $payData = Pay::wechat(config('pay'))->mini(array_merge($payParams, [
            'payer' => [
                'openid' => $user->wxmini_openid,
            ],
        ]));

        $payData['order_id'] = $order->id;
        return response()->json($payData);
    }

    public function quickPayment(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
            'quick_type' => 'filled|integer|in:1',
        ], [], [
            'venue_id' => '场馆 id',
            'quick_type' => '快捷购买类型',
        ]);

        $user = $request->user();
        $venue_id = $request->input('venue_id');
        $quick_type = (int) ($request->input('quick_type', 1));

        if (!$user->wxmini_openid) {
            return response()->json(['message' => '未绑定微信小程序，无法支付'], 403);
        }

        $venue = Venue::query()->where('id', $venue_id)->first();
        if (!$venue) {
            return response()->json(['message' => '场馆不存在'], 403);
        }

        if ($quick_type === 1) {
            $total_amount = '9.90';
            $pay_amount = '9.90';
            $combine_count = 3;
            $chinese_explain = 1;
            $multi_explain = 1;
            $client_type = 1;
            $description = '快捷购买';
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
                'chinese_explain' => $chinese_explain,
                'multi_explain' => $multi_explain,
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
