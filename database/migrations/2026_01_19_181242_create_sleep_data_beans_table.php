<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 睡眠统计 从昨天晚上 8 点到今天晚上 8 点，算今天的时间
    public function up(): void
    {
        Schema::create('sleep_data_beans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->index(['user_id', 'date']);
            $table->unique(['user_id', 'date']);
            $table->unsignedTinyInteger('deep_sleep_count')->nullable()->comment('深睡次数');
            $table->unsignedTinyInteger('light_sleep_count')->nullable()->comment('浅睡次数');
            $table->unsignedTinyInteger('rapid_eye_movement_count')->nullable()->comment('快速眼动次数');
            $table->dateTime('start_at')->comment('开始睡眠时间');
            $table->dateTime('end_at')->nullable()->comment('结束睡眠时间');
            $table->unsignedInteger('deep_sleep_total')->nullable()->comment('深睡总时间');
            $table->unsignedInteger('light_sleep_total')->nullable()->comment('浅睡总时间');
            $table->unsignedInteger('rapid_eye_movement_total')->nullable()->comment('快速眼动时间');
            $table->unsignedTinyInteger('wake_count')->nullable()->comment('清醒次数');
            $table->unsignedTinyInteger('wake_duration')->nullable()->comment('清醒时长，单位秒');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sleep_data_beans');
    }
};
