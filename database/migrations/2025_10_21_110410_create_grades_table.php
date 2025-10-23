<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 班级
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->text('description')->nullable()->comment('描述');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('负责人 id');
            // 毕业时间
            // 班级小程序码
            // 班级总人数
            // 班级封面
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
