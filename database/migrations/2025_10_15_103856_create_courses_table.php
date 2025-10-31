<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 课程
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('创建人');
            $table->string('title')->comment('标题');
            $table->unsignedSmallInteger('duration')->default(0)->comment('时长，单位秒');
            $table->unsignedTinyInteger('category')->comment('分类 1 睡眠 2 专注 3 减压 4 练习');
            $table->unsignedTinyInteger('difficulty')->comment('难度 1 初级 2 中级 3 高级');
            $table->text('description')->nullable()->comment('描述');
            $table->string('cover')->nullable()->comment('封面');
            $table->unsignedBigInteger('tutor_id')->nullable()->comment('导师 id');
            $table->unsignedTinyInteger('status')->default(0)->comment('发布状态 0 待发布 1 已发布');
            $table->unsignedInteger('like_count')->default(0)->comment('喜欢个数');
            $table->unsignedInteger('collect_count')->default(0)->comment('收藏个数');
            $table->unsignedInteger('message_count')->default(0)->comment('留言个数');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
