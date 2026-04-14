<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 点位介绍
    public function up(): void
    {
        Schema::create('place_introductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('place_id')->index();
            $table->string('name')->comment('名称');
            $table->string('content')->comment('内容');
            $table->string('voice')->nullable()->comment('语音');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 上线 2 下线');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('place_introductions');
    }
};
