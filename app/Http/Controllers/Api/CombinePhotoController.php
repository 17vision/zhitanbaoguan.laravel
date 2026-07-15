<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CombinePhoto;
use App\Models\CombineTemplate;
use App\Models\Venue;
use App\Utils\AliOss;
use Illuminate\Http\Request;

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

        $photo = CombinePhoto::create([
            'organization_id' => $venue->organization_id,
            'venue_id' => $venue_id,
            'user_id' => $user->id,
            'combine_album_id' => $template->combine_album_id,
            'combine_template_id' => $template->id,
            'cover' => $cover,
            'photo' => $photoPath,
            'product_img' => null,
            'combine_date' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => '图片生成中',
            'id' => $photo->id,
        ]);
    }
}
