<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Yansongda\Pay\Pay;
use App\Models\Order;
use App\Models\VipOrder;

class PayController extends Controller
{
    private function getOrderNumber(string $value)
    {
        $array = explode('-', $value);
        if (!empty($array)) {
            return $array[0];
        }
        return 0;
    }
    public function wechatNotify(Request $request)
    {
        Pay::config(config('pay'));
        try {
            $res = Pay::wechat()->callback();
            if (!$res) {
                return throw new Exception('无法获取支付成功回调');
            }

            Log::channel('pay-notify')->info('回调数据:', ['data' => $res]);

            $res = $res['resource']['ciphertext'] ?? '';
            if (!$res) {
                return throw new Exception('回调数据错误');
            }

            if (isset($res['attach'])) {

                // 支付通知
                if ($res['attach'] == 'vip') {
                    $order_number = $this->getOrderNumber($res['out_trade_no']);
                    $payment_number = $res['transaction_id'];

                    if ($res['trade_state'] != 'SUCCESS') {
                        return throw new Exception('支付失败');
                    }

                    $order = VipOrder::query()->where('number', $order_number)->first();
                    if (!$order) {
                        return throw new Exception('无法获取订单');
                    }

                    if ($order->status >= 2) {
                        return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
                    }

                    $tradeInfo = $order->trade_info ?? [];
                    $tradeInfo['pay_notify'] = $res;

                    $order->update([
                        'status' => 2,
                        'payment_number' => $payment_number,
                        'paid_at' => isset($res['success_time'])
                            ? Carbon::parse($res['success_time'])->toDateTimeString()
                            : Carbon::now()->toDateTimeString(),
                        'trade_info' => $tradeInfo,
                    ]);

                    $order->grantUserVipRights();

                    Log::channel('pay-notify')->info('vipPayNotify', ['resource' => $res, 'order' => $order]);

                    return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
                }
            } elseif (isset($res['refund_id'])) {
                // 退款通知
                if ($res['refund_status'] == 'SUCCESS') {
                    $outRefundNo = $res['out_refund_no'] ?? '';

                    if (str_starts_with($outRefundNo, 'vip')) {
                        $order = VipOrder::query()->where('payment_number', $res['transaction_id'])->first();
                        if (!$order) {
                            return throw new Exception('无法获取VIP订单');
                        }

                        if ($order->refund_status == 2) {
                            return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
                        }

                        $tradeInfo = $order->trade_info ?? [];
                        $tradeInfo['refund_response'] = $res;

                        if (!isset($tradeInfo['is_vip_rights_back']) || $tradeInfo['is_vip_rights_back'] != 1) {
                            if ($order->rollbackUserVipRights()) {
                                $tradeInfo['is_vip_rights_back'] = 1;
                            }
                        }

                        $data = [
                            'status' => 3,
                            'refund_status' => 2,
                            'refund_at' => isset($res['success_time'])
                                ? Carbon::parse($res['success_time'])->toDateTimeString()
                                : Carbon::now()->toDateTimeString(),
                            'trade_info' => $tradeInfo,
                        ];

                        if ($order->user_refund_status == 2) {
                            $data['user_refund_status'] = 3;
                        }

                        $order->update($data);

                        Log::channel('pay-notify')->info('vipRefundNotify', ['resource' => $res, 'order' => $order]);

                        return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
                    }
                }
            } else {
                return throw new Exception('行为异常');
            }

            return response()->json(['code' => 'SUCCESS', 'message' => '成功']);
        } catch (Exception $e) {
            Log::channel('pay-notify')->error('wechatNotifyError', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

            return response()->json(['message' => '支付回调处理失败'], 403);
        }
    }

    public function payment(Request $request)
    {
        $request->validate([
            'pay_amount' => 'required|numeric'
        ], [], [
            'pay_amount' => '付款金额'
        ]);

        $pay_amount = $request->pay_amount;

        $user = $request->user();

        $out_trade_no = $user->id . '_' . time();

        $data = [
            'out_trade_no' => $out_trade_no,
            'description' => '订单支付',
            'amount' => [
                'total' => $pay_amount * 100,
                'currency' => 'CNY',
            ],
            'payer' => [
                'openid' => $user->wxmini_openid,
            ],
            'attach' => 'payment'
        ];

        $pay = Pay::wechat(config('pay'))->mini($data);

        Log::channel('pay')->info('data', ['data' => $data, 'pay' => $data]);

        return response()->json($pay);
    }

    public function paymentStatus(Request $request) {}
}
