<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 铃声表
    public function up(): void
    {
        Schema::create('ringtones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名字');
            $table->string('introduction')->nullable()->comment('介绍');
            $table->string('thumbnail')->nullable()->comment('图片');
            $table->string('path')->nullable()->comment('音频地址');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 上线 2 下线');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ringtones');
    }
};
