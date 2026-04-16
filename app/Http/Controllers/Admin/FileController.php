<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utils\AliOss;

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

        // referer 验证
        $referer = $info['referer'] ?? '';
        if (!$referer) {
            return response()->json(['message' => '缺少 referer 参数'], 403);
        }

        $referers = ['organization', 'venue', 'place'];
        if (\in_array($referer, $referers)) {
            return response()->json(['message' => 'referer 不在白名单内'], 403);
        }

        // type 验证
        $type = $info['type'] ?? '';
        if (!$type) {
            return response()->json(['message' => '缺少 type 参数'], 403);
        }

        $types = ['video', 'audio', 'image'];
        if (\in_array($type, $types)) {
            return response()->json(['message' => 'type 不在白名单内'], 403);
        }

        // file 文件验证
        if (!$file->isValid()) {
            return response()->json(['error' => 'file 不是有效的文件'], 403);
        }

        $folder = sprintf('zhitanbaoguan/upload/%s/%s/%s/', $referer, $type, date('Ym',  time()));

        // 1. 获取文件后缀（如 jpg/png/pdf）
        $ext = $file->getClientOriginalExtension();

        // 2. 生成 100% 不重复的安全文件名（无中文！无乱码！）
        $fileName = uniqid() . '.' . $ext;

        $ossKey = $folder . $fileName;

        $result = app(AliOss::class)->uploadWebFile($file, $ossKey);

        return response()->json($result);
    }
}
