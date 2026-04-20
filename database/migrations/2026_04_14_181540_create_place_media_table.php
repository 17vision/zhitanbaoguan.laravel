<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 点位媒体介绍
    public function up(): void
    {
        Schema::create('place_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('place_id')->index();
            $table->unsignedTinyInteger('type')->index()->comment('类别 1 图片 2 视频');
            $table->string('name');
            $table->string('path');
            $table->string('thumbnail')->comment('缩略图地址');
            $table->decimal('duration')->nullable()->comment('视频时长，单位秒');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 上线 2 下线');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('place_media');
    }
};
