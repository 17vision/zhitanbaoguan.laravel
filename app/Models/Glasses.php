<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Glasses extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'battery_level', 'system_version', 'equipment_model', 'equipment_sn', 'customer_sn', 'internal_storage_space', 'bluetooth_status', 'bluetooth_name', 'bluetooth_mac_address', 'wifi_status', 'wifi_name_connected', 'wlan_mac_address', 'device_ip_address', 'charging_status', 'bluetooth_inf_device', 'bluetooth_inf_connected', 'camera_temperature_celsius', 'camera_temperature_fahrenheit', 'largespace_map_info', 'trackers', 'qrcode', 'status'];

    protected $appends = ['status_str'];

    public function getStatusStrAttribute()
    {
        $status = $this->getAttribute('status');

        $array = ['', '在线', '离线'];

        return $array[$status] ?? '';
    }

    public function getTrackersAttribute()
    {
        if (isset($this->attributes['trackers']) && $this->attributes['trackers']) {
            return json_decode($this->attributes['trackers'], true);
        }
        return null;
    }
}
