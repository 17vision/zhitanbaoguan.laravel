<?php

return [
    'aliyun' => [
        'region' => env('ALIYUN_OSS_REGION', 'cn-shanghai'),
        'bucket' => env('ALIYUN_OSS_BUCKET', 'zhi-tan-bao-guan'),
        'endPoint' => env('ALIYUN_OSS_ENDPOINT'),
        'accessKeyId' => env('ALIYUN_OSS_ACCESS_KEY_ID'),
        'accessKeySecret' => env('ALIYUN_OSS_ACCESS_KEY_SECRET'),
    ]
];