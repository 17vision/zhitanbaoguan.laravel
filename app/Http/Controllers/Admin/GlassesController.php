<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utils\ImageUpload;
use Carbon\Carbon;
use App\Models\Glasses;

class GlassesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $glasses = Glasses::query()->get();

        return response()->json($glasses);
    }

    public function info(Request $request)
    {
        $glasses = Glasses::query()->get();

        $onlineCount = 0;

        $lowPowerCount = 0;

        $referDate = Carbon::now()->addSeconds(-60);

        foreach ($glasses as $item) {
            if ($item['status'] == 1) {
                $onlineCount++;
            }

            if (percentageToFloat($item['battery_level']) < 0.2) {
                $lowPowerCount++;
            }

            if (Carbon::parse($item['updated_at'])->lt($referDate)) {
                $item->update(['status' => 2, 'trackers' => null]);
            }
        }

        $data = [
            'total_count' => count($glasses),
            'online_count' =>  $onlineCount,
            'lowpower_count' => $lowPowerCount
        ];

        return response()->json($data);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'filled|string',
            'qrcode' => 'filled|string'
        ], [], [
            'id' => '眼镜 id',
            'name' => '眼镜名称',
            'qrcode' => '眼镜名称'
        ]);

        $id = $request->id;

        $data = $request->only(['name', 'qrcode']);

        if (empty($data)) {
            return response()->json(['message' => '没有可更新的数据'], 400);
        }

        $result =  Glasses::query()->where('id', $id)->update($data);

        return response()->json($result);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ], [], [
            'id' => '眼镜 id',
        ]);

        $id = $request->id;

        $result =  Glasses::query()->where('id', $id)->delete();

        return response()->json($result);
    }

    public function buildQrcode(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'width' => 'filled|integer|min:240',
        ], [], [
            'id' => '眼镜 id',
            'width' => '小程序码宽度'
        ]);

        $id = $request->input('id');
        $width = $request->input('width', 640);

        $glasses =  Glasses::query()->where('id', $id)->first();
        if (!$glasses) {
            return response()->json(['message' => '眼镜不存在'], 403);
        }

        $sn = $glasses->equipment_sn;

        $data = [
            'scene' => 'device_id=' . $sn,
            'page' => 'large-space/pages/product-list/product-list',
            'width' => $width
        ];

        $base64Image = app(ImageController::class)->getWxcode($data);

        $folder = sprintf('storage/upload/glasses/%s/', Carbon::parse($glasses->created_at)->format('Ym'));

        $name = $sn . '_wxcode.png';

        $result = app(ImageUpload::class)->saveBase64Image($base64Image, $folder, $name);
        if ($result && isset($result['error']) && $result['error']) {
            return response()->json(['message' => $result['error']], 403);
        }

        if ($result['url']) {
            $glasses->update([
                'qrcode' => $result['url']
            ]);
            return response()->json(storageUrl($result['url']));
        }
        return response()->json(['message' => '生成失败'], 403);
    }
}
