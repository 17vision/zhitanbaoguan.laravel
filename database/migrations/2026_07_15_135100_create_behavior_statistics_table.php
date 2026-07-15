<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('behavior_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->comment('组织 id');
            $table->unsignedBigInteger('venue_id')->comment('场馆 id');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('用户 id');
            $table->date('date')->index()->comment('日期');
            $table->tinyInteger('type')->nullable()->comment('类型 1 打开小程序 2 使用讲解');
            $table->bigInteger('target_id')->nullable()->comment('目标ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('behavior_statistics');
    }
};
