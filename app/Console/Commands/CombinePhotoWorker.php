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
    private const RUN_SECONDS = 7200;

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
            $sleepSeconds = random_int(1, 10);
            sleep($sleepSeconds);

            $startedAt = time();

            while ((time() - $startedAt) < self::RUN_SECONDS) {
                // 续期 worker 锁，避免接近 2 小时时提前过期
                Redis::expire($workerKey, self::WORKER_TTL);

                $photo = CombinePhoto::query()
                    ->where('status', CombinePhoto::STATUS_PENDING)
                    ->where('combine_date', '>=', now()->subDays(3)->toDateString())
                    ->orderBy('id')
                    ->first();

                if (!$photo) {
                    sleep(2);
                    continue;
                }

                $lockKey = "combine_photo:lock:{$photo->id}";
                // 已被其它脚本锁定
                if (!Redis::set($lockKey, $flag, 'EX', self::RECORD_LOCK_TTL, 'NX')) {
                    sleep(2);
                    continue;
                }

                try {
                    $this->processPhoto($photo, $flag);
                } catch (\Throwable $e) {
                    $photo->refresh();
                    $photo->status = CombinePhoto::STATUS_FAILED;
                    $photo->failreason = mb_substr($e->getMessage(), 0, 1000);
                    $photo->save();

                    Log::channel('cron')->error("CombinePhotoWorker flag={$flag} id={$photo->id} exception", [
                        'message' => $e->getMessage(),
                    ]);
                } finally {
                    Redis::del($lockKey);
                    // 合成耗时长，处理后立刻续期 worker 锁
                    Redis::expire($workerKey, self::WORKER_TTL);
                }
            }

            Log::channel('cron')->info("CombinePhotoWorker flag={$flag} finished after 2 hours");
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
            $reason = '缺少模板图或用户照片';
            $photo->status = CombinePhoto::STATUS_FAILED;
            $photo->failreason = $reason;
            $photo->save();

            return;
        }

        $result = app(SeeDream::class)->swapFace($templateUrl, $faceUrl);
        if (!($result['success'] ?? false)) {
            $reason = $result['error'] ?? '合成图片失败';
            $photo->status = CombinePhoto::STATUS_FAILED;
            $photo->failreason = $reason;
            $photo->save();

            return;
        }

        $folder = sprintf('zhitanbaoguan/upload/combine/product/%s/', date('Ym'));
        $fileName = $photo->user_id . '_' . uniqid() . '.jpg';
        $ossKey = $folder . $fileName;

        $upload = app(AliOss::class)->uploadFromUrl($result['url'], $ossKey);
        if (!($upload['success'] ?? false)) {
            $reason = $upload['error'] ?? '合成图上传失败';
            $photo->status = CombinePhoto::STATUS_FAILED;
            $photo->failreason = $reason;
            $photo->save();

            return;
        }

        $productPath = ossToPath($upload['url'] ?? '');
        if (!$productPath) {
            $reason = '合成图上传失败';
            $photo->status = CombinePhoto::STATUS_FAILED;
            $photo->failreason = $reason;
            $photo->save();

            return;
        }

        $photo->product_img = $productPath;
        $photo->status = CombinePhoto::STATUS_SUCCESS;
        $photo->failreason = null;
        $photo->save();
    }
}
