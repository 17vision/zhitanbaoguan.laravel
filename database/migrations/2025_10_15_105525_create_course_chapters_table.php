<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 课程章节
    public function up(): void
    {
        Schema::create('course_chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('创建人');
            $table->unsignedBigInteger('course_id')->index()->comment('课程 id');
            $table->string('title')->comment('标题');
            $table->text('description')->nullable()->comment('描述');
            $table->unsignedBigInteger('resource_id')->index()->nullable()->comment('资源 id');
            $table->unsignedSmallInteger('index')->default(0)->comment('排序');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_chapters');
    }
};
