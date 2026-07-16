<?php

namespace App\Utils;

use AlibabaCloud\Oss\V2 as Oss;
use AlibabaCloud\Oss\V2\Models\PutObjectRequest;
use AlibabaCloud\Oss\V2\Credentials\StaticCredentialsProvider;
class AliOss
{
    private $region = 'cn-shanghai';
    private $bucket = 'zhi-tan-bao-guan';
    private $endPoint = 'https://ztbg-oss.17vision.com';

      /**
     * 上传 Laravel $request->file('file') 到 OSS
     * @param mixed $file 前端上传的 $request->file('file')
     * @param string $ossKey 上传到 oss 的目录+文件名
     * @return array
     */
    public function uploadWebFile($file, string $ossKey)
    {
        try {
            // 1. 获取文件信息
            $originalName = $file->getClientOriginalName(); // 原文件名
            // 3. 读取文件流（Laravel 临时文件）
            $filePath = $file->getPathname();
            $stream = fopen($filePath, 'r');
            $body = Oss\Utils::streamFor($stream);

            // 4. OSS 配置
            // $credentialsProvider = new Oss\Credentials\EnvironmentVariableCredentialsProvider();
            $credentialsProvider = new StaticCredentialsProvider(
                config('oss.aliyun.accessKeyId'),
                config('oss.aliyun.accessKeySecret')
            );

            $cfg = Oss\Config::loadDefault();
            $cfg->setCredentialsProvider($credentialsProvider);
            $cfg->setRegion($this->region);
            // $cfg->setEndpoint($this->endPoint);

            $client = new Oss\Client($cfg);

            // 5. 上传请求
            $request = new PutObjectRequest($this->bucket, $ossKey);
            $request->body = $body;
  
            // 执行上传
            $client->putObject($request);
            fclose($stream);

            // 6. 返回线上地址
            $url =  $this->getOssFileUrl($ossKey);

            return [
                'success' => true,
                'url' => $url,
                'path' => $ossKey,
                'name' => $originalName
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 从公网 URL 下载文件并上传到 OSS
     * @param string $fileUrl 源文件地址
     * @param string $ossKey 上传到 oss 的目录+文件名
     * @param int $timeout 下载超时秒数
     * @return array
     */
    public function uploadFromUrl(string $fileUrl, string $ossKey, int $timeout = 60)
    {
        try {
            $content = curl($fileUrl, false, false, true, $timeout);
            if ($content === false || $content === '') {
                return [
                    'success' => false,
                    'error' => '下载合成图片失败',
                ];
            }

            $credentialsProvider = new StaticCredentialsProvider(
                config('oss.aliyun.accessKeyId'),
                config('oss.aliyun.accessKeySecret')
            );

            $cfg = Oss\Config::loadDefault();
            $cfg->setCredentialsProvider($credentialsProvider);
            $cfg->setRegion($this->region);

            $client = new Oss\Client($cfg);

            $request = new PutObjectRequest($this->bucket, $ossKey);
            $request->body = Oss\Utils::streamFor($content);
            $client->putObject($request);

            $url = $this->getOssFileUrl($ossKey);

            return [
                'success' => true,
                'url' => $url,
                'path' => $ossKey,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取文件访问URL
     */
    private function getOssFileUrl(string $key): string
    {
        if ($this->endPoint) {
            return $this->endPoint . "/" . $key;
        }
        return "https://{$this->bucket}.oss.{$this->region}.aliyuncs.com/{$key}";
    }
}