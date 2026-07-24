<?php

namespace App\Console\Commands;

use App\Models\CombinePhoto;
use App\Utils\AliOss;
use App\Utils\SeeDream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CombinePhotoWorker extends Command
{
    protected $signature = 'app:combine-photo {flag : 脚本标识，用于多实例区分}';

    protected $description = '异步合成照片（多实例常驻，单实例按 flag 互斥）';

    private const WORKER_TTL = 150;
    private const RECORD_LOCK_TTL = 120;
    private const RUN_SECONDS = 3600;

    public function handle()
    {
        $flag = (string) $this->argument('flag');
        $workerKey = "combine_photo:worker:{$flag}";

        // 同 flag 已在跑则退出
        if (!Redis::set($workerKey, getmypid() ?: 1, 'EX', self::WORKER_TTL, 'NX')) {
            return self::SUCCESS;
        }

        try {
            // 随机错开启动
            sleep(mt_rand(1, 10));

            $startedAt = time();

            while ((time() - $startedAt) < self::RUN_SECONDS) {
                // 续期 worker 锁
                Redis::expire($workerKey, self::WORKER_TTL);

                $photo = CombinePhoto::query()
                    ->where('status', CombinePhoto::STATUS_PENDING)
                    ->where('combine_date', '>=', now()->subDays(15)->toDateString())
                    ->inRandomOrder()
                    ->first();

                if (!$photo) {
                    sleep(mt_rand(1, 10));
                    continue;
                }

                $lockKey = "combine_photo:lock:{$photo->id}";
                // 已被其它脚本锁定
                if (!Redis::set($lockKey, $flag, 'EX', self::RECORD_LOCK_TTL, 'NX')) {
                    sleep(1);
                    continue;
                }

                try {
                    $this->processPhoto($photo, $flag);
                } catch (\Throwable $e) {
                    $photo->refresh();
                    $photo->markFailed(mb_substr($e->getMessage(), 0, 1000));

                    Log::channel('cron')->error("CombinePhotoWorker flag={$flag} id={$photo->id} exception", [
                        'message' => $e->getMessage(),
                    ]);
                } finally {
                    Redis::del($lockKey);
                    Redis::expire($workerKey, self::WORKER_TTL);
                }
            }

            Log::channel('cron')->info("CombinePhotoWorker flag={$flag} finished");
        } finally {
            Redis::del($workerKey);
        }

        return self::SUCCESS;
    }

    private function processPhoto(CombinePhoto $photo, string $flag): void
    {
        // 双重确认仍为待合成
        $photo->refresh();
        if ((int) $photo->status !== CombinePhoto::STATUS_PENDING) {
            return;
        }

        $photo->status = CombinePhoto::STATUS_PROCESSING;
        $photo->failreason = null;
        $photo->save();

        $templateUrl = $photo->cover;
        $faceUrl = $photo->photo;
        if (!$templateUrl || !$faceUrl) {
            $photo->markFailed('缺少模板图或用户照片');

            return;
        }

        $result = app(SeeDream::class)->swapFace($templateUrl, $faceUrl);
        if (!($result['success'] ?? false)) {
            $photo->markFailed($result['error'] ?? '合成图片失败');

            return;
        }

        $folder = sprintf('zhitanbaoguan/upload/combine/product/%s/', date('Ym'));
        $fileName = $photo->user_id . '_' . uniqid() . '.jpg';
        $ossKey = $folder . $fileName;

        $upload = app(AliOss::class)->uploadFromUrl($result['url'], $ossKey);
        if (!($upload['success'] ?? false)) {
            $photo->markFailed($upload['error'] ?? '合成图上传失败');

            return;
        }

        $productPath = ossToPath($upload['url'] ?? '');
        if (!$productPath) {
            $photo->markFailed('合成图上传失败');

            return;
        }

        $photo->product_img = $productPath;
        $photo->status = CombinePhoto::STATUS_SUCCESS;
        $photo->failreason = null;
        $photo->save();
    }
}
