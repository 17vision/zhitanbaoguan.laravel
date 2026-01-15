<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 用户课程统计
    public function up(): void
    {
        Schema::create('course_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户 id');
            $table->date('date')->comment('观看课程日期');
            $table->unsignedBigInteger('course_id')->comment('课程id');
            $table->unsignedBigInteger('course_chapter_id')->comment('章节id');
            $table->index(['user_id', 'course_id', 'course_chapter_id', 'date', 'cs_uid_cid_chid_date_index']);
            $table->unique(['user_id', 'course_id', 'course_chapter_id', 'date'], 'cs_uid_cid_chid_date_unq');
            $table->unsignedInteger('duration')->comment('观看总时长，单位秒');
            $table->unsignedInteger('position')->nullable()->comment('观看位置，单位秒');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('course_chapter_id')->references('id')->on('course_chapters')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_statistics');
    }
};
