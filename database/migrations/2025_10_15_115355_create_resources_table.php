<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 资源
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('创建人');
            $table->unsignedBigInteger('resource_group_id')->index();
            $table->string('name', 32);
            $table->unsignedTinyInteger('type')->comment('1 图片 2 视频 3 音频 4 模型');
            $table->string('path')->comment('素材地址');
            $table->string('thumbnail')->nullable();
            $table->string('remark')->nullable()->comment('备注');
            $table->unsignedSmallInteger('usage_count')->default(0)->comment('资源被使用次数');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
