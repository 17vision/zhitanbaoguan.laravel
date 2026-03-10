<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Yansongda\Pay\Pay;

class PayController extends Controller
{
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
                // 支付

            } elseif (isset($res['refund_id'])) {
                // 退款
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
