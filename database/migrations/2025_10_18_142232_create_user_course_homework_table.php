<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 用户的作业
    public function up(): void
    {
        Schema::create('user_homework', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('作业人');
            $table->unsignedBigInteger('homework_id')->index()->comment('作业 id');
            $table->text('content')->comment('作业内容，根据作业生成对应的json');
            $table->decimal('score')->nullable()->comment('分数');
            $table->string('evaluation')->nullable()->comment('评审');
            $table->dateTime('end_at')->nullable()->comment('结束时间');
            $table->dateTime('completed_at')->nullable()->comment('完成时间');
            $table->unsignedTinyInteger('status')->default(0)->comment('发布状态 0 待完成 1 已完成 2 未完成');
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
*/