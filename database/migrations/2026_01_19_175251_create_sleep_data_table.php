<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 睡眠数据
    public function up(): void
    {
        Schema::create('sleep_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sleep_data_bean_id')->comment('睡眠数据 id');
            $table->index(['user_id', 'sleep_data_bean_id']);
            $table->unsignedTinyInteger('type')->comment('睡眠类型 0 未知, 1 深睡, 2 浅睡, 3 快速眼动, 4 清醒');
            $table->dateTime('start_at')->comment('睡眠开始时间');
            $table->dateTime('end_at')->comment('睡眠结束时间');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sleep_data');
    }
};
