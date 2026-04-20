<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Utils\ImageUpload;
use App\Models\Place;

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
            return response()->json(['message' => '缺少 referer 信息'], 403);
        }

        $referers = ['avatar', 'place', 'venue'];
        if (!\in_array($referer, $referers)) {
            return response()->json(['message' => 'referer 不在白名单呢'], 403);
        }

        $use = $info['use'] ?? '';

        $uses = [
            'place' => ['qrcode']
        ];

        if ($use && isset($uses[$referer])) {
            if (!\in_array($use, $uses[$referer])) {
                return response()->json(['message' => 'use 不在白名单呢'], 403);
            }
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
        } elseif ($referer == 'place') {
            if (isset($info['id']) && $info['id']) {
                $place = Place::query()->where('id', $info['id'])->first();
                $packageName = $place->created_at->format('Ym');
                $name = $place->id;
            } else {
                $packageName = date('Ym', time());
                $name = randStr(8);
            }

            if ($use) {
                $folder = sprintf('storage/upload/image/%s/%s/%s/', $referer, $use, $packageName);
            } else {
                $folder = sprintf('storage/upload/image/%s/%s/', $referer, $packageName);
            }
        } else {
            if ($use) {
                $folder = sprintf('storage/upload/image/%s/%s/%s/', $referer, $use, date('Ym', time()));
            } else {
                $folder = sprintf('storage/upload/image/%s/%s/', $referer, date('Ym', time()));
            }
            $name = randStr(8);
            $max_width = 240;
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

        $result = curl($url, json_encode($data), true, true);

        try {
            $res = json_decode($result, true);
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                Log::channel('error')->error('get-wxcode-error', ['url' => $url,  'data' => $data, 'result' => $result]);
                return false;
            }
        } catch (\Exception $e) {
            Log::channel('error')->error('get-wxcode-error', ['url' => $url,  'data' => $data, 'result' => $result, 'message' => $e->getMessage()]);
        }

        $image = "data:image/jpeg;base64," . base64_encode($result);

        return $image;
    }
}
