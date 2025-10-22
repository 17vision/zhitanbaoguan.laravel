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
            $table->unsignedBigInteger('course_id')->index()->comment('课程id');
            $table->unsignedBigInteger('course_chapter_id')->index()->comment('章节id');
            $table->unsignedBigInteger('user_id')->index()->comment('用户 id');
            $table->unsignedInteger('duration')->comment('观看总时长，单位秒'); 
            $table->unsignedInteger('position')->nullable()->comment('观看位置，单位秒'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_statistics');
    }
};
