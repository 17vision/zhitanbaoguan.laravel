<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 场景组别
    public function up(): void
    {
        Schema::create('scene_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->string('introduction')->nullable()->comment('介绍');
            $table->unsignedInteger('scene_nums')->default(0)->comment('组别 id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scene_categories');
    }
};
