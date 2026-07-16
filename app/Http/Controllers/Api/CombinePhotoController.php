<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CombinePhoto;
use App\Models\CombineTemplate;
use App\Models\UserReceive;
use App\Models\Venue;
use App\Models\VipUser;
use App\Utils\AliOss;
use App\Utils\SeeDream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function combinePicture(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:combine_photos,id',
        ], [], [
            'id' => '合成记录 id',
        ]);

        $user = $request->user();
        $photo = CombinePhoto::query()
            ->where('id', $request->input('id'))
            ->first();

        if (!$photo) {
            return response()->json(['message' => '合成记录不存在'], 403);
        }

        // 已有成品图，直接返回可预览地址
        if ($photo->getRawOriginal('product_img')) {
            return response()->json([
                'product_img' => $photo->product_img,
            ]);
        }

        $templateUrl = $photo->cover;
        $faceUrl = $photo->photo;
        if (!$templateUrl || !$faceUrl) {
            return response()->json(['message' => '缺少模板图或用户照片'], 403);
        }

        $result = app(SeeDream::class)->swapFace($templateUrl, $faceUrl);
        if (!($result['success'] ?? false)) {
            return response()->json(['message' => $result['error'] ?? '合成图片失败'], 403);
        }

        $folder = sprintf('zhitanbaoguan/upload/combine/product/%s/', date('Ym'));
        $fileName = $user->id . '_' . uniqid() . '.jpg';
        $ossKey = $folder . $fileName;

        $upload = app(AliOss::class)->uploadFromUrl($result['url'], $ossKey);
        if (!($upload['success'] ?? false)) {
            return response()->json(['message' => $upload['error'] ?? '合成图上传失败'], 403);
        }

        $productPath = ossToPath($upload['url'] ?? '');
        if (!$productPath) {
            return response()->json(['message' => '合成图上传失败'], 403);
        }

        $photo->product_img = $productPath;
        $photo->save();

        return response()->json([
            'product_img' => $photo->product_img,
        ]);
    }

}
