<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 消息回复点赞
    public function up(): void
    {
        Schema::create('course_message_reply_praises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_message_reply_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unique(['course_message_reply_id', 'user_id']);
            $table->unsignedBigInteger('course_message_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('course_message_reply_id')->references('id')->on('course_message_replies')->cascadeOnDelete();
            $table->foreign('course_message_id')->references('id')->on('course_messages')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_message_reply_praises');
    }
};
