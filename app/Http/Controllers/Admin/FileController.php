<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'info' => 'required',
            'file' => 'required'
        ], [], [
            'info' => '文件信息',
            'file' => '文件'
        ]);

        $file = $request->file;

        $info = $request->info;

        if (gettype($info) == 'string') {
            $info = json_decode($info, true);
        }

        $referer = $info['referer'] ?? '';
        if (!$referer) {
            return response()->json(['message' => __('messages.msgs.msg8')], 403);
        }

        if (!$file->isValid()) {
            return response()->json(['error' => __('messages.msgs.msg9')], 403);
        }

        if ($referer == 'resource') {
            $type = $info['type'] ?? '';
            $types = ['video', 'audio', 'model'];

            if (!in_array($type, $types)) {
                return response()->json(['message' => '资源类型不正确'], 403);
            }

          
            $folder = sprintf('storage/upload/%s/%s/%s/', $referer, $type, date('Ym',  time()));

            $fileType = $file->getClientOriginalExtension();

            $fileName = randStr(8) . '.' . $fileType;

            $filePath = $file->getRealPath();

            Storage::disk('file')->put($folder . $fileName, file_get_contents($filePath));

            $url = $folder . $fileName;

            return response()->json(storageUrl($url));
        }

        return response()->json(['message' => '不被支持的 referer'], 403);
    }
}
