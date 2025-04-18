<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function uploadVideo(Request $request)
    {
        $request->validate(
            [
                'file' => 'required',
                'referer' => 'required|string'
            ],
            [],
            [
                'file' => '视频文件',
                'referer' => '视频来源'
            ]
        );

        $referer = $request->referer;

        $file = $request->file;

        if (!$file->isValid()) {
            return response()->json(['error' => '文件有问题'], 403);
        }

        $user = $request->user();

        if ($referer == 'moment') {
            $folder = sprintf('upload/video/%s/%s/', $referer, date('Ymd', time()));

            $fileType = $file->getClientOriginalExtension(); #获取文件后缀

            $fileName = $user->id . '_' . date('Ymdhis') . randStr(6) . '.' . $fileType;

            $filePath = $file->getRealPath(); #获取文件临时存放位置

            Storage::disk('video')->put($folder . $fileName, file_get_contents($filePath));

            $url = Storage::url($folder . $fileName);

            return response()->json(['url' => storageUrl($url)]);
        }
        return response()->json(['error' => '你该何去何从'], 403);
    }
}
