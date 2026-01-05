<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 主题
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->string('introduction')->comment('介绍');
            $table->string('head')->comment('头像');
            $table->string('path')->comment('主题地址');
            $table->unsignedInteger('like_nums')->default(0)->comment('喜欢数目');
            $table->unsignedInteger('unlike_nums')->default(0)->comment('不喜欢数目');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 上线 2 下线');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
