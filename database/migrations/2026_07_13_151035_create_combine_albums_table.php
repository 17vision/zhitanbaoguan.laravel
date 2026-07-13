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
        Schema::create('combine_albums', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index()->comment('组织 id');
            $table->unsignedBigInteger('venue_id')->index()->comment('场馆 id');
            $table->string('name')->comment('相册分类名称');
            $table->string('cover')->nullable()->comment('分类封面图');
            $table->text('introduction')->nullable()->comment('分类介绍文案');
            $table->unsignedInteger('sort')->default(0)->comment('排序权重');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态 0待上线 1上线 2下线');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combine_albums');
    }
};
