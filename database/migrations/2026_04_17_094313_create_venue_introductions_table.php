<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 场馆介绍
    public function up(): void
    {
        Schema::create('venue_introductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venue_id')->index()->comment('场馆 id');
            $table->string('name')->comment('名称');
            $table->text('content')->comment('内容');
            $table->string('voice')->nullable()->comment('语音');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 上线 2 下线');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_introductions');
    }
};
