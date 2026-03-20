<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Glasses;
use Illuminate\Http\Request;

class GlassesController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'equipment_sn' => 'required|string'
        ], [], [
            'equipment_sn' => '设备序列号'
        ]);

        $keys = ['battery_level', 'system_version', 'equipment_model', 'equipment_sn', 'customer_sn', 'internal_storage_space', 'bluetooth_status', 'bluetooth_name', 'bluetooth_mac_address', 'wifi_status', 'wifi_name_connected', 'wlan_mac_address', 'device_ip_address', 'charging_status', 'bluetooth_inf_device', 'bluetooth_inf_connected', 'camera_temperature_celsius', 'camera_temperature_fahrenheit', 'largespace_map_info', 'trackers'];

        $data = $request->only($keys);

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = null;
            }
        }

        $data['status'] = 1;

        if (isset($data['trackers']) && $data['trackers']) {
            $trackers = json_decode($data['trackers'], true);
            $ok = true;

            foreach ($trackers as $item) {
                if (!isset($item['tracker_name']) || !$item['tracker_name']) {
                    $ok = false;
                    break;
                }

                if (!isset($item['tracker_battery_level']) || !$item['tracker_battery_level']) {
                    $ok = false;
                    break;
                }
            }

            if (!$ok) {
                return response()->json(['message' => '跟踪器数据错误'], 403);
            }
        }

        $glasses = Glasses::query()->where('equipment_sn', $data['equipment_sn'])->first();
        if ($glasses) {
            $glasses->update($data);
        } else {
            $glasses = Glasses::create($data);
        }

        return response()->json($glasses);
    }
}
