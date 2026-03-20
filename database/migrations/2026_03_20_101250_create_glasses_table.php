<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 眼镜
    public function up(): void
    {
        Schema::create('glasses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment('设备名称');
            $table->string('battery_level')->nullable()->comment('电量');
            $table->string('system_version')->nullable()->comment('系统版本');
            $table->string('equipment_model')->nullable()->comment('设备型号');
            $table->string('equipment_sn')->comment('设备序列号');
            $table->unique(['equipment_sn']);
            $table->string('customer_sn')->nullable()->comment('客户序列号');
            $table->string('internal_storage_space')->nullable()->comment('设备存储');
            $table->string('bluetooth_status')->nullable()->comment('蓝牙状态');
            $table->string('bluetooth_name')->nullable()->comment('蓝牙名称');
            $table->string('bluetooth_mac_address')->nullable()->comment('蓝牙mac地址');
            $table->string('wifi_status')->nullable()->comment('wifi状态');
            $table->string('wifi_name_connected')->nullable()->comment('已连接的wifi状态');
            $table->string('wlan_mac_address')->nullable()->comment('wlan mac 地址');
            $table->string('device_ip_address')->nullable()->comment('设备 ip 地址');
            $table->string('charging_status')->nullable()->comment('设备充电状态');
            $table->text('bluetooth_inf_device')->nullable()->comment('有关设备原始蓝牙信息');
            $table->text('bluetooth_inf_connected')->nullable()->comment('已连接蓝牙信息');
            $table->decimal('camera_temperature_celsius', 5, 2)->nullable()->comment('相机的温度，摄氏度');
            $table->decimal('camera_temperature_fahrenheit', 5, 2)->nullable()->comment('相机温度，华氏度');
            $table->text('largespace_map_info')->nullable()->comment('大空间地图信息');
            $table->text('trackers')->nullable()->comment('追踪器');
            $table->string('qrcode')->nullable()->comment('体验小程序码');
            $table->unsignedTinyInteger('status')->default(1)->comment('1 上线 2 下线');
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('glasses');
    }
};
