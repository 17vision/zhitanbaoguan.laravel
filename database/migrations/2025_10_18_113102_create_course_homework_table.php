<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 课程作业 (这个确认是否还需要)
    public function up(): void
    {
        Schema::create('course_homework', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('创建人');
            $table->unsignedBigInteger('course_id')->index()->comment('课程 id');
            $table->string('title')->comment('标题');
            $table->string('content')->nullable()->comment('内容');
            $table->text('config')->nullable()->comment('配置');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_homework');
    }
};

// 样例
/***
{
    "user_id": 1,
    "course_id": 1,
    "title": "第一周-五感觉知",
    "content": "每天选择 1 项五感练习，记录当下体验与感受",
    "config": [
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