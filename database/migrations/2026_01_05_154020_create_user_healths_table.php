<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 用户健康表
    public function up(): void
    {
        Schema::create('user_healths', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedTinyInteger('heart_rate')->nullable()->comment('心率 30-250 次/分');
            $table->decimal('blood_oxygen', 5, 2)->nullable()->comment('血氧 0.00-100.00 %，保留两位小数');
            $table->decimal('systolic', 5, 1)->nullable()->comment('收缩压（高压） 50-300 mmHg');
            $table->decimal('diastolic', 5, 1)->nullable()->comment('舒张压（低压） 30-200 mmHg');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_healths');
    }
};


// 心率 104 bpm
// 血氧 98%
// 血压 132/87