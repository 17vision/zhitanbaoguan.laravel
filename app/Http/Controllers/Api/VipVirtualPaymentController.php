<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\VipOrder;
use App\Services\VipVirtualOrderService;
use App\Utils\WeChatVirtualPay;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VipVirtualPaymentController extends Controller
{
    public function __construct(
        private WeChatVirtualPay $virtualPay,
        private VipVirtualOrderService $orderService
    ) {
    }

    /**
     * 虚拟支付快捷下单：返回 wx.requestVirtualPayment 参数
     */
    public function quickPayment(Request $request)
    {
        if (!config('virtual_pay.enabled')) {
            return response()->json(['message' => '虚拟支付未开启'], 403);
        }

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

        if (!$user->wxmini_session_key) {
            return response()->json(['message' => '登录态失效，请重新登录后再支付'], 403);
        }

        if (!$this->virtualPay->offerId() || !$this->virtualPay->appKey()) {
            return response()->json(['message' => '虚拟支付配置不完整'], 403);
        }

        $order = null;
        $outTradeNo = null;
        $productId = null;

        if ($request->filled('order_id')) {
            $order = VipOrder::query()
                ->where('id', $request->input('order_id'))
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json(['message' => '订单不存在'], 403);
            }

            if ((int) $order->payment_channel !== VipOrder::PAYMENT_CHANNEL_VIRTUAL) {
                return response()->json(['message' => '该订单不是虚拟支付订单'], 403);
            }

            if ((int) $order->status !== 1) {
                return response()->json(['message' => '订单状态不允许支付'], 403);
            }

            $orderNumber = explode('-', $order->number)[0];
            $outTradeNo = $orderNumber . '-' . random_int(1000000, 9999999);
            $productId = $order->product_id ?: null;
        } else {
            $venueId = (int) $request->input('venue_id');
            $quickType = (int) ($request->input('quick_type', 1));
            $product = $this->virtualPay->productConfig($quickType);

            if (!$product) {
                return response()->json(['message' => '不支持的快捷购买类型'], 403);
            }

            $venue = Venue::query()->where('id', $venueId)->first();
            if (!$venue) {
                return response()->json(['message' => '场馆不存在'], 403);
            }

            $productId = (string) $product['product_id'];
            $env = $this->virtualPay->env();

            DB::beginTransaction();
            try {
                $order = VipOrder::create([
                    'organization_id' => $venue->organization_id,
                    'venue_id' => $venueId,
                    'user_id' => $user->id,
                    'vip_package_id' => null,
                    'combine_count' => (int) $product['combine_count'],
                    'vip_duration' => (int) $product['vip_duration'],
                    'total_amount' => $product['total_amount'],
                    'pay_amount' => $product['pay_amount'],
                    'payment_type' => 1,
                    'payment_channel' => VipOrder::PAYMENT_CHANNEL_VIRTUAL,
                    'product_id' => $productId,
                    'env' => $env,
                    'goods_delivered' => 0,
                    'status' => 1,
                    'client_type' => 1,
                ]);
                $outTradeNo = $order->number;
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::channel('error')->error('vip虚拟支付订单创建失败', [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);

                return response()->json(['message' => '创建订单失败'], 403);
            }
        }

        if (!$productId) {
            return response()->json(['message' => '订单缺少道具配置'], 403);
        }

        $goodsPrice = $this->virtualPay->yuanToFen((string) $order->pay_amount);
        $env = (int) ($order->env ?? $this->virtualPay->env());

        try {
            $payParams = $this->virtualPay->buildGoodsPayParams([
                'offerId' => $this->virtualPay->offerId(),
                'buyQuantity' => 1,
                'env' => $env,
                'currencyType' => 'CNY',
                'productId' => $productId,
                'goodsPrice' => $goodsPrice,
                'outTradeNo' => $outTradeNo,
                'attach' => 'vip',
            ], (string) $user->wxmini_session_key);
        } catch (Exception $e) {
            Log::channel('pay')->error('virtualPaySignFail', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => '生成支付参数失败'], 403);
        }

        $tradeInfo = $order->trade_info ?? [];
        $tradeInfo['virtual_out_trade_no'] = $outTradeNo;
        $tradeInfo['virtual_sign_at'] = Carbon::now()->toDateTimeString();
        $order->update(['trade_info' => $tradeInfo]);

        Log::channel('pay')->info('virtualPayQuick', [
            'order_id' => $order->id,
            'out_trade_no' => $outTradeNo,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'order_id' => $order->id,
            'mode' => $payParams['mode'],
            'signData' => $payParams['signData'],
            'paySig' => $payParams['paySig'],
            'signature' => $payParams['signature'],
        ]);
    }

    /**
     * 查询订单状态；若待支付则尝试向微信查单并补发货
     */
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

        if (
            (int) $order->payment_channel === VipOrder::PAYMENT_CHANNEL_VIRTUAL
            && (int) $order->status === 1
            && (int) $order->goods_delivered === 0
        ) {
            $this->orderService->trySyncPaidOrder($order);
            $order->refresh();
        }

        return response()->json($order);
    }

    /**
     * 虚拟支付消息推送（发货 / iOS 退款问询 / 退款结果）
     */
    public function notify(Request $request)
    {
        if ($request->isMethod('get')) {
            return $this->verifyUrl($request);
        }

        $payload = $this->parseNotifyPayload($request);
        Log::channel('pay-notify')->info('virtualPayNotifyRaw', [
            'payload' => $payload,
            'content_type' => $request->header('Content-Type'),
        ]);

        if (!$payload) {
            return $this->notifyFail('invalid payload');
        }

        if (isset($payload['Encrypt']) && !isset($payload['Event'])) {
            Log::channel('pay-notify')->error('virtualPayNotifyEncrypted', ['payload' => $payload]);

            return $this->notifyFail('encrypted message not supported');
        }

        $event = (string) ($payload['Event'] ?? '');

        try {
            return match ($event) {
                'xpay_goods_deliver_notify' => $this->handleGoodsDeliver($payload),
                'xpay_refund_notify' => $this->handleRefundNotify($payload),
                'xpay_subscribe_ios_refund_query_notify' => $this->handleIosRefundQuery($payload),
                default => $this->handleUnknownEvent($event, $payload),
            };
        } catch (Exception $e) {
            Log::channel('pay-notify')->error('virtualPayNotifyError', [
                'event' => $event,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->notifyFail($e->getMessage());
        }
    }

    private function verifyUrl(Request $request)
    {
        $echostr = (string) $request->query('echostr', '');
        $token = (string) config('virtual_pay.token', '');

        if ($token === '') {
            return response($echostr, 200)->header('Content-Type', 'text/plain');
        }

        $signature = (string) $request->query('signature', '');
        $timestamp = (string) $request->query('timestamp', '');
        $nonce = (string) $request->query('nonce', '');
        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = sha1(implode($tmpArr));

        if ($tmpStr === $signature) {
            return response($echostr, 200)->header('Content-Type', 'text/plain');
        }

        return response('invalid', 403);
    }

    private function parseNotifyPayload(Request $request): ?array
    {
        $raw = $request->getContent();
        if ($raw === '' || $raw === false) {
            return $request->all() ?: null;
        }

        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }

        $xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml !== false) {
            return json_decode(json_encode($xml), true);
        }

        return $request->all() ?: null;
    }

    private function handleGoodsDeliver(array $payload)
    {
        $outTradeNo = (string) ($payload['OutTradeNo'] ?? '');
        if ($outTradeNo === '') {
            throw new Exception('缺少 OutTradeNo');
        }

        $order = $this->orderService->findOrderByOutTradeNo($outTradeNo);
        if (!$order) {
            throw new Exception('无法获取订单: ' . $outTradeNo);
        }

        $wechatPayInfo = $payload['WeChatPayInfo'] ?? [];
        $transactionId = (string) ($wechatPayInfo['TransactionId'] ?? ($payload['TransactionId'] ?? ''));
        $paidTime = $wechatPayInfo['PaidTime'] ?? null;

        $this->orderService->markPaidAndDeliver($order, $transactionId, $paidTime, [
            'goods_deliver_notify' => $payload,
        ]);

        return $this->notifyOk();
    }

    private function handleRefundNotify(array $payload)
    {
        $retCode = (int) ($payload['RetCode'] ?? -1);
        if ($retCode !== 0) {
            Log::channel('pay-notify')->warning('virtualPayRefundNotSuccess', ['payload' => $payload]);

            return $this->notifyOk();
        }

        $mchOrderId = (string) ($payload['MchOrderId'] ?? '');
        $wxOrderId = (string) ($payload['WxOrderId'] ?? '');
        $transactionId = (string) ($payload['WxpayRefundTransactionId'] ?? ($payload['WxTransactionId'] ?? ''));

        $order = null;
        if ($mchOrderId !== '') {
            $order = $this->orderService->findOrderByOutTradeNo($mchOrderId);
        }
        if (!$order && $transactionId !== '') {
            $order = VipOrder::query()
                ->where('payment_channel', VipOrder::PAYMENT_CHANNEL_VIRTUAL)
                ->where('payment_number', $transactionId)
                ->first();
        }
        if (!$order && $wxOrderId !== '') {
            $order = VipOrder::query()
                ->where('payment_channel', VipOrder::PAYMENT_CHANNEL_VIRTUAL)
                ->where('payment_number', $wxOrderId)
                ->first();
        }

        if (!$order) {
            throw new Exception('退款通知找不到订单');
        }

        DB::transaction(function () use ($order, $payload) {
            $locked = VipOrder::query()->where('id', $order->id)->lockForUpdate()->first();
            if (!$locked) {
                return;
            }

            if ((int) $locked->refund_status === 2 && (int) $locked->status === 3) {
                return;
            }

            $tradeInfo = $locked->trade_info ?? [];
            $tradeInfo['virtual_refund_notify'] = $payload;

            if (!isset($tradeInfo['is_vip_rights_back']) || (int) $tradeInfo['is_vip_rights_back'] !== 1) {
                if ($locked->rollbackUserVipRights()) {
                    $tradeInfo['is_vip_rights_back'] = 1;
                }
            }

            $data = [
                'status' => 3,
                'refund_status' => 2,
                'refund_at' => isset($payload['RefundSuccTimestamp'])
                    ? Carbon::createFromTimestamp((int) $payload['RefundSuccTimestamp'])->toDateTimeString()
                    : Carbon::now()->toDateTimeString(),
                'trade_info' => $tradeInfo,
            ];

            if ((int) $locked->user_refund_status === 2) {
                $data['user_refund_status'] = 3;
            }

            $locked->update($data);
        });

        Log::channel('pay-notify')->info('virtualPayRefundNotify', [
            'order_id' => $order->id,
            'payload' => $payload,
        ]);

        return $this->notifyOk();
    }

    /**
     * iOS 退款问询：一律建议同意退款
     */
    private function handleIosRefundQuery(array $payload)
    {
        Log::channel('pay-notify')->info('virtualPayIosRefundQuery', ['payload' => $payload]);

        $contentType = (string) request()->header('Content-Type', '');
        $wantsJson = str_contains($contentType, 'json') || request()->isJson();

        if ($wantsJson) {
            return response()->json([
                'ErrCode' => 0,
                'ErrMsg' => 'success',
                'result_code' => 0,
                'result_info' => 'auto approve refund inquiry',
                'evidence' => '系统自动同意退款问询',
                'ResultCode' => 0,
                'ResultInfo' => 'auto approve refund inquiry',
                'Evidence' => '系统自动同意退款问询',
            ]);
        }

        $xml = '<xml>'
            . '<ErrCode>0</ErrCode>'
            . '<ErrMsg><![CDATA[success]]></ErrMsg>'
            . '<result_code>0</result_code>'
            . '<result_info><![CDATA[auto approve refund inquiry]]></result_info>'
            . '<evidence><![CDATA[系统自动同意退款问询]]></evidence>'
            . '</xml>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    private function handleUnknownEvent(string $event, array $payload)
    {
        Log::channel('pay-notify')->info('virtualPayNotifyUnknownEvent', [
            'event' => $event,
            'payload' => $payload,
        ]);

        return $this->notifyOk();
    }

    private function notifyOk()
    {
        return response()->json(['ErrCode' => 0, 'ErrMsg' => 'success']);
    }

    private function notifyFail(string $msg)
    {
        return response()->json(['ErrCode' => -1, 'ErrMsg' => $msg], 200);
    }
}
