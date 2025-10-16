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
            ],
            [],
            [
                'file' => '图片文件',
                'referer' => '类'
            ]
        );

        $file = $request->file;

        $referer = $request->referer;

        $user = $request->user();

        if ($referer == 'avatar') {
            $folder = sprintf('storage/upload/image/%s/%s/', $referer, date('Ym', strtotime($user->created_at)));
            $name = $user->id;
            $max_width = 200;

            if ($request->hasFile('file')) {
                $result = app(ImageUpload::class)->saveFileImage($file, $folder, $name, $max_width);
            } else {
                $name = $name . '.jpg';
                $result = app(ImageUpload::class)->saveBase64Image($file, $folder, $name, $max_width);
            }

            if ($result && isset($result['error']) && $result['error']) {
                return response()->json(['message' => $result['error']], 403);
            }

            $url = $result['url'] . '?time=' . time();

            $user->update(['avatar' => $url]);

            return response()->json(['url' => storageUrl($url)]);
        }

        return  response()->json(['message' => '没有对应的 referer'], 403);
    }
}
