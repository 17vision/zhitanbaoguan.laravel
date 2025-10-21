<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 作业 (课程作业就不要了)
    public function up(): void
    {
        Schema::create('homework', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('创建人');
            $table->unsignedBigInteger('homework_group_id')->index()->comment('分组 id');
            $table->string('title')->comment('标题');
            $table->string('content')->nullable()->comment('内容');
            $table->text('config')->nullable()->comment('配置');
            $table->unsignedBigInteger('resource_id')->index()->nullable()->comment('资源 id。对应音频或视频');
            $table->unsignedTinyInteger('status')->default(0)->comment('发布状态 0 待发布 1 已发布');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework');
    }
};
