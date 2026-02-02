<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // 场景统计
    public function up(): void
    {
        Schema::create('scene_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scene_id')->index()->comment('场景 id');
            $table->unsignedTinyInteger('type')->index()->comment('类型 1 专注 2 睡眠 3 小憩 4 呼吸');
            $table->unsignedBigInteger('user_id')->index()->comment('用户 id');
            $table->unsignedInteger('duration')->comment('时长，单位秒');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scene_statistics');
    }
};
