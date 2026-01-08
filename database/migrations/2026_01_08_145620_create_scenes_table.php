<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 场景
    public function up(): void
    {
        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scene_category_id')->index()->comment('分组 id');
            $table->string('name')->comment('名称');
            $table->string('introduction')->comment('介绍');
            $table->string('image')->nullable()->comment('图片地址');
            $table->string('video')->nullable()->comment('视频地址');
            $table->string('tag')->nullable()->comment('标签');
            $table->unsignedInteger('like_nums')->default(0)->comment('喜欢数目');
            $table->unsignedInteger('collect_nums')->default(0)->comment('收藏数目');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 未发布 2 已发布');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenes');
    }
};
