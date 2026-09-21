<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 微信小程序虚拟支付（道具直购）
    |--------------------------------------------------------------------------
    | 与普通微信支付 config/pay.php 分离；在 MP「虚拟支付」开通后填写。
    | 文档：https://developers.weixin.qq.com/miniprogram/dev/platform-capabilities/business-capabilities/virtual-payment.html
    */

    'enabled' => (bool) env('WX_VIRTUAL_PAY_ENABLED', false),

    'offer_id' => env('WX_VIRTUAL_OFFER_ID', ''),

    // env=0 现网 AppKey；env=1 沙箱 AppKey
    'app_key' => env('WX_VIRTUAL_APP_KEY', ''),
    'app_key_sandbox' => env('WX_VIRTUAL_APP_KEY_SANDBOX', ''),

    // 0 现网 / 1 沙箱
    'env' => (int) env('WX_VIRTUAL_ENV', 0),

    // 消息推送 Token（可选，用于 URL 接入校验）
    'token' => env('WX_VIRTUAL_TOKEN', ''),

    // 待支付超时关闭（分钟）
    'close_after_minutes' => (int) env('WX_VIRTUAL_CLOSE_AFTER_MINUTES', 30),

    // 已创建未支付但可能已付未回调：超过该分钟后查单补发货
    'query_after_minutes' => (int) env('WX_VIRTUAL_QUERY_AFTER_MINUTES', 2),

    /*
    |--------------------------------------------------------------------------
    | 快捷购买 quick_type => 道具与业务权益映射
    | product_id 须与 MP 道具管理中已发布的道具 ID 一致
    |--------------------------------------------------------------------------
    */
    'products' => [
        1 => [
            'product_id' => env('WX_VIRTUAL_PRODUCT_QUICK_1', 'vip_quick_1'),
            'total_amount' => env('WX_VIRTUAL_QUICK_1_AMOUNT', '9.90'),
            'pay_amount' => env('WX_VIRTUAL_QUICK_1_AMOUNT', '9.90'),
            'combine_count' => (int) env('WX_VIRTUAL_QUICK_1_COMBINE_COUNT', 3),
            'vip_duration' => (int) env('WX_VIRTUAL_QUICK_1_VIP_DURATION', 86400),
            'description' => '快捷购买',
        ],
    ],

];
