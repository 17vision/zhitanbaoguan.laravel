<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 每日一句
    public function up(): void
    {
        Schema::create('daily_sentences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('date');
            $table->string('title')->comment('标题');
            $table->string('text')->comment('文案');
            $table->string('author')->comment('作者');
            $table->string('image')->nullable()->comment('图片');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sentences');
    }
};
