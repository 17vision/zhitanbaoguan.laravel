<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 课程收藏
    public function up(): void
    {
        Schema::create('course_collects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->index()->comment('课程 id');
            $table->unsignedBigInteger('user_id')->index()->comment('创建人');
            $table->unique(['course_id', 'user_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_collects');
    }
};
