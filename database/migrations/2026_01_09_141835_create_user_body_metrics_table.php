<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 用户身体指标表
    public function up(): void
    {
        Schema::create('user_body_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('height', 4, 1)->comment('身高(cm)');
            $table->decimal('weight', 4, 1)->comment('体重(kg)');
            $table->unsignedTinyInteger('age')->comment('年龄(岁)');
            $table->decimal('body_fat_pct', 3, 1)->nullable()->comment('体质率 22.5%, 填 22.5 就可以');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_body_metrics');
    }
};
