<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Glasses;
use Carbon\Carbon;

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
}
