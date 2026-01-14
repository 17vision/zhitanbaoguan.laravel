<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 运动数据
    public function up(): void
    {
        Schema::create('user_daily_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date')->comment('日');
            $table->unsignedTinyInteger('hour')->comment('小时');
            $table->unique(['user_id', 'date', 'hour']);
            $table->index(['user_id', 'date', 'hour']);
            $table->unsignedInteger('steps')->default(0)->comment('步数');
            $table->unsignedInteger('calories')->default(0)->comment('卡路里(kcal)');
            $table->decimal('distance', 6, 2)->default(0)->comment('距离(km)');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_daily_steps');
    }
};
