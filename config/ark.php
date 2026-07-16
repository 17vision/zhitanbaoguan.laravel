<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 火山方舟 Ark / Seedream
    |--------------------------------------------------------------------------
    */

    'api_key' => env('ARK_API_KEY'),
    'model_id' => env('ARK_MODEL_ID', 'doubao-seedream-5-0-pro-260628'),
    'api_url' => env('ARK_API_URL', 'https://ark.cn-beijing.volces.com/api/v3/images/generations'),
    'timeout' => (int) env('ARK_TIMEOUT', 120),

];
