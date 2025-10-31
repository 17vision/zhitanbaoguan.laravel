<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 课程留言
    public function up(): void
    {
        Schema::create('course_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('content')->comment('留言内容');
            $table->unsignedTinyInteger('type')->comment('留言内容类型 1 文字 2 图片 3 视频');
            $table->unsignedInteger('praises_nums')->default(0)->comment('点赞的个数');
            $table->unsignedInteger('reply_nums')->default(0)->comment('回复的个数');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_messages');
    }
};
