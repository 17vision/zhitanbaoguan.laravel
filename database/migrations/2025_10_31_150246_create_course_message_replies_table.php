<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 课程留言回复 
    public function up(): void
    {
        Schema::create('course_message_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_message_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('content')->comment('留言内容');
            $table->unsignedTinyInteger('type')->comment('留言内容类型 1 文字 2 图片 3 视频');
            $table->unsignedBigInteger('course_message_reply_id')->nullable()->comment('回复二级的');
            $table->unsignedInteger('praises_nums')->default(0)->comment('点赞的个数');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_message_replies');
    }
};
