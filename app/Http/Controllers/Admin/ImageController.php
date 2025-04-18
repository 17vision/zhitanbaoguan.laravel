<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Utils\ImageUpload;

class ImageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'info' => 'sometimes|array',
            'file' => 'required|string'
        ], [], [
            'info' => '图片信息',
            'file' => '图片文件'
        ]);

        $user = $request->user();

        $info = $request->info;

        $referer = isset($info) && isset($info['referer']) ? $info['referer'] : '';

        if ($referer == 'avatar') {
            $folder = sprintf('storage/upload/image/avatar/%s/', date('Ym', strtotime($user->created_at)));
            $name = $user->id . '.jpg';
            $max_width = 320;
        }

        $res = app(ImageUpload::class)->saveBase64Image($request->file, $folder, $name, $max_width);
        if ($res && isset($res['error'])) {

            Log::channel('error')->error($res);

            return response()->json(['message' => '上传图片失败'], 403);
        }

        $path = $res['url'] . '?time=' . time();

        if ($referer == 'avatar') {
            $user->update(['avatar' => $path]);
        }
        return response()->json(storageUrl($path), 200);
    }
}
