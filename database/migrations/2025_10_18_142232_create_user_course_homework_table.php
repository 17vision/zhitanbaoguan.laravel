<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_course_homework', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('作业人');
            $table->unsignedBigInteger('course_id')->index()->comment('课程 id');
            $table->unsignedBigInteger('course_homework_id')->index()->comment('课程作业 id');
            $table->text('content')->comment('作业内容，根据作业生成对应的json');
            $table->decimal('score')->nullable()->comment('分数');
            $table->string('evaluation')->nullable()->comment('评审');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_course_homework');
    }
};

// 样例
/***
{
    "user_id": 1,
    "course_id": 1,
    "course_homework_id": 1,
    "content": [
        {
            "label": "练习类型",
            "placeholder": "例如：听觉觉察、视觉觉察"
        },
        {
            "label": "练习情景",
            "placeholder": "在哪里练习？当时的环境如何"
        },
        {
            "label": "觉察感受",
            "placeholder": "过程中感受到了什么？有哪些想法浮现"
        }
    ]
}
