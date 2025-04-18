<?php

return [
    /*
     * 接口频率限制
     */
    'rate_limits' => [
        // 其他频率限制，次数/分钟
        'access' =>  env('RATE_LIMITS_ACCESS', '60,1'),
        // 登录相关，次数/分钟
        'sign' =>  env('RATE_LIMITS_SIGN', '30,1'),
    ],

    'secret' => env('API_SECRET', ''),

    // 是否允许跨域（后台部分才需要）
    'enable_cross' => env('ENABLE_CROSS', false),

    // 允许跨的域
    'allow_origin' => explode('|', env('ALLOW_ORIGIN', '')),
];
