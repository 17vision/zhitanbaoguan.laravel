<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Utils\ImageUpload;

class ImageController extends Controller
{
    public function uploadImages(Request $request)
    {
        $request->validate(
            [
                'file' => 'required',
                'referer' => 'required|string',
                'sign' => 'required|string'
            ],
            [],
            [
                'file' => '图片文件',
                'referer' => '类'
            ]
        );

        $referer = $request->referer;

        $sign = $request->sign;

        $user = $request->user();

        if ($referer == 'avatar') {
            $folder = sprintf('storage/upload/image/%s/%s/', $referer, date('Ym', strtotime($user->created_at)));
            $raw_name = $user->id . '_raw.jpg';
            $name = $user->id . '.jpg';
            $max_width = 200;

            $result = app(ImageUpload::class)->saveBinaryImage(file_get_contents($request->file), $folder, $name, $max_width, $raw_name);
            if ($result && isset($result['error']) && $result['error']) {
                return response()->json(['message' => $result['error']], 403);
            }

            $raw = $result['raw'] . '?time=' . time();
            $url = $result['url'] . '?time=' . time();

            // 如果是 upage_bg 是默认的，就一起变化了
            if (Str::contains($user->upage_bg, 'static/image')) {
                $user->update(['avatar' => $url, 'avatar_raw' => $raw, 'upage_bg' => $raw]);
            } else {
                $user->update(['avatar' => $url, 'avatar_raw' => $raw]);
            }
            return response()->json(['avatar' => storageUrl($url), 'avatar_raw' => storageUrl($raw)]);
        }
    }
}
