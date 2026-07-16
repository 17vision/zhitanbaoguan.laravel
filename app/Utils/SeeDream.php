<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;

class SeeDream
{
    private string $apiKey;
    private string $modelId;
    private string $apiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) config('ark.api_key', '');
        $this->modelId = (string) config('ark.model_id', 'doubao-seedream-5-0-pro-260628');
        $this->apiUrl = (string) config('ark.api_url', 'https://ark.cn-beijing.volces.com/api/v3/images/generations');
        $this->timeout = (int) config('ark.timeout', 120);
    }

    /**
     * 人脸融合（Seedream 同步接口，超时默认 120 秒）
     *
     * @param string $templateUrl 模板图公网 HTTPS URL
     * @param string $faceUrl 用户人脸图公网 HTTPS URL
     * @return array{success: bool, url?: string, error?: string, raw?: mixed}
     */
    public function swapFace(string $templateUrl, string $faceUrl): array
    {
        if ($this->apiKey === '') {
            return [
                'success' => false,
                'error' => 'ARK_API_KEY 未配置',
            ];
        }

        if ($templateUrl === '' || $faceUrl === '') {
            return [
                'success' => false,
                'error' => '缺少模板图或用户照片 URL',
            ];
        }

        // 官方多图参考：image 为数组，prompt 用「图1/图2」指代
        // 图1=模板底图，图2=用户人脸
        $prompt = '将图1中人物的人脸替换为图2中的人脸。以图1作为唯一画布，严格锁定原图尺寸、画面边界、构图，禁止扩图、禁止延展画面、禁止补全边缘。图1内所有元素原样保留：人物姿势、古装、帽子、盔甲、手持道具、背景、光影色调完全不变。将图2人脸完整迁移，图2人物佩戴的墨镜、镜框、镜片形状与反光原样移植至图1人物面部，严禁删除、替换、虚化墨镜。只修改面部皮肤区域，其余全部像素与图1保持一致，照片写实风格，肤色自然统一。';

        $postBody = [
            'model' => $this->modelId,
            'prompt' => $prompt,
            'image' => [
                $templateUrl, // 图1：模板
                $faceUrl,     // 图2：用户人脸
            ],
            'response_format' => 'url',
            'size' => '1K',
            'stream' => false,
            'watermark' => false,
        ];

        $payload = json_encode($postBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $respRaw = curl(
            $this->apiUrl,
            $payload,
            true,
            true,
            $this->timeout,
            ['Authorization: Bearer ' . $this->apiKey]
        );

        if ($respRaw === false) {
            return [
                'success' => false,
                'error' => '请求方舟接口失败',
            ];
        }

        $resp = json_decode($respRaw, true);
        $imageUrl = $resp['data'][0]['url'] ?? null;

        if (!empty($imageUrl)) {
            return [
                'success' => true,
                'url' => $imageUrl,
                'raw' => $resp,
            ];
        }

        $error = $resp['error']['message']
            ?? $resp['message']
            ?? '生成失败';

        Log::channel('single')->warning('SeeDream swapFace failed', [
            'response' => $resp,
        ]);

        return [
            'success' => false,
            'error' => $error,
            'raw' => $resp,
        ];
    }
}
