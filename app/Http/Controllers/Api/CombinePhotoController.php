<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CombinePhoto;
use App\Models\CombineTemplate;
use App\Models\UserReceive;
use App\Models\Venue;
use App\Models\VipUser;
use App\Utils\AliOss;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CombinePhotoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
        ], [], [
            'venue_id' => '场馆 id',
        ]);

        $photos = CombinePhoto::query()
            ->where('user_id', $request->user()->id)
            ->where('venue_id', $request->venue_id)
            ->orderByDesc('combine_date')
            ->orderByDesc('id')
            ->get();

        $data = $photos->groupBy(function ($photo) {
            return $photo->combine_date ? $photo->combine_date->format('Y-m-d') : '';
        })->map(function ($items, $date) {
            return [
                'combine_date' => $date,
                'photos' => $items->values(),
            ];
        })->values();

        return response()->json($data);
    }

    public function generatePhoto(Request $request)
    {
        $request->validate([
            'venue_id' => 'required|integer|exists:venues,id',
            'template_id' => 'required|integer|exists:combine_templates,id',
            'photo' => 'required|file|image',
        ], [], [
            'venue_id' => '场馆 id',
            'template_id' => '模板 id',
            'photo' => '用户照片',
        ]);

        $user = $request->user();
        $venue_id = $request->input('venue_id');
        $today = now()->toDateString();

        $venue = Venue::query()->where('id', $venue_id)->first();
        if (!$venue) {
            return response()->json(['message' => '场馆不存在'], 403);
        }

        $template = CombineTemplate::query()
            ->where('id', $request->input('template_id'))
            ->where('status', 1)
            ->first();
        if (!$template) {
            return response()->json(['message' => '模板不存在或已下架'], 403);
        }

        $receive = UserReceive::query()
            ->where('user_id', $user->id)
            ->where('venue_id', $venue_id)
            ->whereDate('date', $today)
            ->first();

        $vipUser = VipUser::query()
            ->where('user_id', $user->id)
            ->where('venue_id', $venue_id)
            ->first();

        $receiveCombineCount = $receive ? (int) $receive->combine_count : 0;
        $vipCombineCount = $vipUser ? (int) $vipUser->combine_count : 0;

        if ($receiveCombineCount <= 0 && $vipCombineCount <= 0) {
            return response()->json(['message' => '合成次数不足'], 403);
        }

        $file = $request->file('photo');
        if (!$file || !$file->isValid()) {
            return response()->json(['message' => 'photo 不是有效的文件'], 403);
        }

        $folder = sprintf('zhitanbaoguan/upload/combine/image/%s/', date('Ym'));
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = $user->id . '_' . uniqid() . '.' . $ext;
        $ossKey = $folder . $fileName;

        $result = app(AliOss::class)->uploadWebFile($file, $ossKey);
        if (!($result['success'] ?? false)) {
            return response()->json(['message' => $result['error'] ?? '照片上传失败'], 403);
        }

        $photoPath = ossToPath($result['url'] ?? '');
        if (!$photoPath) {
            return response()->json(['message' => '照片上传失败'], 403);
        }

        $cover = $template->getRawOriginal('cover') ?: '';

        $photo = DB::transaction(function () use ($venue, $venue_id, $user, $template, $cover, $photoPath, $today) {
            $photo = CombinePhoto::create([
                'organization_id' => $venue->organization_id,
                'venue_id' => $venue_id,
                'user_id' => $user->id,
                'combine_album_id' => $template->combine_album_id,
                'combine_template_id' => $template->id,
                'cover' => $cover,
                'photo' => $photoPath,
                'product_img' => null,
                'combine_date' => $today,
            ]);

            $receive = UserReceive::query()
                ->where('user_id', $user->id)
                ->where('venue_id', $venue_id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if ($receive && (int) $receive->combine_count > 0) {
                $receive->decrement('combine_count');
            } else {
                $vipUser = VipUser::query()
                    ->where('user_id', $user->id)
                    ->where('venue_id', $venue_id)
                    ->lockForUpdate()
                    ->first();

                if ($vipUser && (int) $vipUser->combine_count > 0) {
                    $vipUser->decrement('combine_count');
                }
            }

            return $photo;
        });

        return response()->json([
            'message' => '图片生成中',
            'id' => $photo->id,
        ]);
    }

    public function combineTest(Request $request)
    {
        // ========== 配置区 修改这里 ==========

        $VOLC_VISUAL_AK = env('VOLC_VISUAL_AK');
        $VOLC_VISUAL_SK = env('VOLC_VISUAL_SK');

// 模板图(第1张)、用户人脸图(第2张) 【必须公网HTTPS URL】
        $templateUrl = $request->input('templateUrl');
        $faceUrl = $request->input('faceUrl');
//        $templateUrl = "https://xxx/古风模板.jpg";
//        $faceUrl     = "https://xxx/人脸照片.jpg";

// 🔥你的公网HTTPS回调地址
        $callbackUrl = "https://ztbg.17vision.com/api/combine-notify";

        $region    = 'cn-north-1';
        $service   = 'cv';
        $host      = 'visual.volcengineapi.com';
        $action    = 'CVSync2AsyncSubmitTask';
        $version   = '2022-08-31';
// ======================================

        $body = [
            "req_key" => "high_aes_general_v22",
            "image_urls" => [
                $templateUrl,
                $faceUrl
            ],
            "prompt" => "将第一张图片中的人物人脸替换为第二张图片的人脸，严格保留原图人物姿势、盔甲、服饰、背景、光照、色彩氛围，人脸和原图光影自然融合，五官比例协调，无扭曲变形，五官比例正常，边缘无锯齿，不扭曲、不发白，适配室内仪器屏幕拍摄的人脸，画面整体风格统一。",
            "negative_prompt" => "人脸扭曲，五官错位，畸形，色差严重，光影割裂，肢体变形，多余肢体，模糊，水印，文字，五官变形，脸部发白，肤色断层，边缘模糊，多出来的五官，画面过曝，暗部发黑",
            "return_url" => true,
            "callback_url" => $callbackUrl // 传入回调地址
        ];
        $payload = json_encode($body);
        $xDate = gmdate('Y-m-d\TH:i:s\Z');

        $authorization = $this->buildV4Sign($action, $version, $xDate, $payload, $VOLC_VISUAL_AK, $VOLC_VISUAL_SK, $region, $service, $host);

        $headers = [
            "Host: {$host}",
            "Content-Type: application/json; charset=utf-8",
            "X-Date: {$xDate}",
            "Authorization: {$authorization}"
        ];
        $url = "https://{$host}?Action={$action}&Version={$version}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "HTTP状态码:".$httpCode.PHP_EOL;
        echo "响应内容：".$respRaw.PHP_EOL;

        $resp = json_decode($respRaw,true);
        if(!empty($resp['Result']['TaskId'])){
            echo PHP_EOL."==== 提交成功 ====".PHP_EOL;
            echo "TaskId = ".$resp['Result']['TaskId'].PHP_EOL;
            echo "等待火山生成图片后，主动推送数据到：".$callbackUrl.PHP_EOL;
        }else{
            echo PHP_EOL."任务提交失败！".PHP_EOL;
        }
    }

    /**
     * 火山V4签名算法（固定无需改动）
     */
    function buildV4Sign(string $action, string $version, string $xDate, string $payload, string $ak, string $sk, string $region, string $service, string $host): string
    {
        $shortDate = substr($xDate, 0, 10);
        $credentialScope = "{$shortDate}/{$region}/{$service}/request";

        $canonicalUri = "/";
        $canonicalQuery = "Action={$action}&Version={$version}";
        $canonicalHeaders = "content-type:application/json; charset=utf-8\nhost:{$host}\nx-date:{$xDate}\n";
        $signedHeaders = "content-type;host;x-date";
        $hashedPayload = hash("sha256", $payload);

        $canonicalRequest = implode("\n", [
            'POST', $canonicalUri, $canonicalQuery, $canonicalHeaders, $signedHeaders, $hashedPayload
        ]);
        $canonicalHash = hash('sha256', $canonicalRequest);

        $stringToSign = implode("\n", [
            "HMAC-SHA256",
            $xDate,
            $credentialScope,
            $canonicalHash
        ]);

        $kDate = hash_hmac('sha256', $shortDate, $sk, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', "request", $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return "HMAC-SHA256 Credential={$ak}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
    }

    public function combineNotify(Request $request)
    {
// 接收火山异步回调
// 1、读取原始POST数据
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

// 日志记录（调试用，正式环境保留日志）
        Log::channel('combine-notify')->info('回调数据:', ['data' => $data]);

        if(empty($data['TaskId'])){
            http_response_code(400);
            exit("empty taskid");
        }

        $taskId = $data['TaskId'];
        $status = $data['Status'];

        echo "ok"; // 🔥必须返回纯字符串 ok，200状态码！
        flush();

// ========== 业务逻辑写在这里 ==========
        /*
        状态说明
        Success：生成成功
        Failed：生成失败

        成功结构示例：
        [
            "TaskId"=>"xxx",
            "Status"=>"Success",
            "ImageUrls"=>["https://火山临时图片地址"],
        ]
        失败会携带 Message 错误信息
        */

        if($status === "Success"){
            $imageUrl = $data['ImageUrls'][0];
            // TODO：根据taskId 更新 combine_photos
            // 1、下载图片到你的OSS
            // 2、更新数据库 generate_status=1 填入product_img
        }elseif($status === "Failed"){
            $errMsg = $data['Message'] ?? "";
            // TODO：更新数据库 generate_status=2，记录错误信息
        }
    }
}
