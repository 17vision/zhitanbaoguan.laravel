<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;

class WeChatVirtualPay
{
    public const MODE_GOODS = 'short_series_goods';

    public const URI_REQUEST_VIRTUAL_PAYMENT = 'requestVirtualPayment';

    public const URI_QUERY_ORDER = '/xpay/query_order';

    public function env(): int
    {
        return (int) config('virtual_pay.env', 0);
    }

    public function offerId(): string
    {
        return (string) config('virtual_pay.offer_id', '');
    }

    public function appKey(?int $env = null): string
    {
        $env = $env ?? $this->env();

        if ($env === 1) {
            return (string) config('virtual_pay.app_key_sandbox', '');
        }

        return (string) config('virtual_pay.app_key', '');
    }

    public function productConfig(int $quickType): ?array
    {
        $products = config('virtual_pay.products', []);

        return $products[$quickType] ?? null;
    }

    /**
     * 组装 wx.requestVirtualPayment 所需参数（signData 原样给前端，勿再 JSON 序列化）
     *
     * @return array{signData: string, paySig: string, signature: string, mode: string, order_id: int, out_trade_no: string}
     */
    public function buildGoodsPayParams(array $payload, string $sessionKey): array
    {
        $signDataArr = [
            'attach' => (string) ($payload['attach'] ?? 'vip'),
            'buyQuantity' => (int) ($payload['buyQuantity'] ?? 1),
            'currencyType' => (string) ($payload['currencyType'] ?? 'CNY'),
            'env' => (int) ($payload['env'] ?? $this->env()),
            'goodsPrice' => (int) $payload['goodsPrice'],
            'offerId' => (string) ($payload['offerId'] ?? $this->offerId()),
            'outTradeNo' => (string) $payload['outTradeNo'],
            'productId' => (string) $payload['productId'],
        ];

        // 字典序，保证签名与传给前端的字符串一致
        ksort($signDataArr);

        $signData = json_encode($signDataArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [
            'signData' => $signData,
            'paySig' => $this->calcPaySig(self::URI_REQUEST_VIRTUAL_PAYMENT, $signData, $signDataArr['env']),
            'signature' => $this->calcUserSignature($signData, $sessionKey),
            'mode' => self::MODE_GOODS,
            'out_trade_no' => $signDataArr['outTradeNo'],
        ];
    }

    public function calcPaySig(string $uri, string $postBody, ?int $env = null): string
    {
        $appKey = $this->appKey($env);
        $needSignMsg = $uri . '&' . $postBody;

        return hash_hmac('sha256', $needSignMsg, $appKey);
    }

    public function calcUserSignature(string $postBody, string $sessionKey): string
    {
        return hash_hmac('sha256', $postBody, $sessionKey);
    }

    /**
     * 查询虚拟支付订单（现金单）
     *
     * @return array{success: bool, order?: array, error?: string, raw?: mixed}
     */
    public function queryOrder(string $openid, string $orderId, ?int $env = null): array
    {
        $env = $env ?? $this->env();
        $accessToken = getWxMiniAccessToken(config('auth.wxmini.appid'), config('auth.wxmini.secret'));
        if (!$accessToken) {
            return ['success' => false, 'error' => '获取 access_token 失败'];
        }

        $postBodyArr = [
            'env' => $env,
            'openid' => $openid,
            'order_id' => $orderId,
        ];
        ksort($postBodyArr);
        $postBody = json_encode($postBodyArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $paySig = $this->calcPaySig(self::URI_QUERY_ORDER, $postBody, $env);

        $url = sprintf(
            'https://api.weixin.qq.com/xpay/query_order?access_token=%s&pay_sig=%s',
            urlencode($accessToken),
            urlencode($paySig)
        );

        $respRaw = curl($url, $postBody, true, true);
        if ($respRaw === false) {
            return ['success' => false, 'error' => '请求 query_order 失败'];
        }

        $resp = json_decode($respRaw, true);
        if (!is_array($resp)) {
            return ['success' => false, 'error' => 'query_order 返回非 JSON', 'raw' => $respRaw];
        }

        if (($resp['errcode'] ?? -1) !== 0) {
            Log::channel('pay')->warning('virtualPayQueryOrderFail', ['resp' => $resp, 'order_id' => $orderId]);

            return [
                'success' => false,
                'error' => $resp['errmsg'] ?? 'query_order 失败',
                'raw' => $resp,
            ];
        }

        return [
            'success' => true,
            'order' => $resp['order'] ?? [],
            'raw' => $resp,
        ];
    }

    /**
     * 元转分
     */
    public function yuanToFen(string $yuan): int
    {
        return (int) bcmul($yuan, '100', 0);
    }
}
