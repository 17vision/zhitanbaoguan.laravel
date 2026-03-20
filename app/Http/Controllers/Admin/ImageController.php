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
            'info' => 'required',
            'file' => 'required'
        ], [], [
            'info' => '图片信息',
            'file' => '图片文件'
        ]);

        $info = $request->info;

        $file = $request->file;

        if (gettype($info) == 'string') {
            $info = json_decode($info, true);
        }

        $referer = $info['referer'] ?? '';
        if (!$referer) {
            return response()->json(['message' => '缺少 info 信息'], 403);
        }

        $user = $request->user();

        $both = $info['both'] ?? false;
        if ($both) {
            $thumbnail_name = 'min_' . randStr(8);
            $min_width = 300;
        } else {
            $thumbnail_name = null;
            $min_width = null;
        }

        if ($referer == 'avatar') {
            $folder = sprintf('storage/upload/image/avatar/%s/', date('Ym', strtotime($user->created_at)));
            $name = $user->id;
            $max_width = 320;
        } elseif ($referer == 'resource') {
            $type = $info['type'] ?? '';
            $types = ['image', 'video', 'audio', 'model'];

            if (!in_array($type, $types)) {
                return response()->json(['message' => '资源类型不正确'], 403);
            }
            // 资源
            $folder = sprintf('storage/upload/image/resource/%s/%s/', $type, date('Ym', time()));
            $name = 'max_' . randStr(8);
            $max_width = null;
        } elseif ($referer == 'course' || $referer == 'course_chapter') {
            // 课程
            $folder = sprintf('storage/upload/image/%s/%s/', $referer, date('Ym', time()));
            $name = randStr(8);
            $max_width = null;
        } elseif ($referer == 'tutor') {
            $folder = sprintf('storage/upload/image/tutor/%s/', date('Ym', time()));
            $name = randStr(8);
            $max_width = 240;
        } else {
            return  response()->json(['message' => '没有对应的 referer'], 403);
        }

        if ($request->hasFile('file')) {
            $res = app(ImageUpload::class)->saveFileImage($file, $folder, $name, $max_width, $thumbnail_name, $min_width);
        } else {
            $name = $name . '.jpg';

            $res = app(ImageUpload::class)->saveBase64Image($file, $folder, $name, $max_width, $thumbnail_name, $min_width);
        }

        if ($res && isset($res['error'])) {
            Log::channel('error')->error('upload-image-error', $res);
            return response()->json(['message' => '上传图片失败'], 403);
        }

        if ($referer == 'avatar') {
            $path = $res['url'] . '?time=' . time();
            $user->update(['avatar' => $path]);
        }

        $url = $res['url'] ? storageUrl($res['url']) : '';

        $thumbnail = $res['thumbnail'] ? storageUrl($res['thumbnail']) : '';

        return response()->json(['url' => $url, 'thumbnail' => $thumbnail]);
    }

    public function getWxcode($data)
    {
        $access_token = getAccessToken(config('auth.wxmini.appid'), config('auth.wxmini.secret'));

        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;

        $result = curl($url, true, json_encode($data));

        if (!$result) {
            return false;
        }

        $image = "data:image/jpeg;base64," . base64_encode($result);

        return $image;
    }
}
