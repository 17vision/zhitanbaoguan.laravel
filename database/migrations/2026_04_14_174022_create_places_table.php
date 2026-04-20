<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 点位表，支持无限嵌套
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index()->comment('组织 id');
            $table->unsignedBigInteger('venue_id')->index()->comment('场馆 id');
            $table->unsignedBigInteger('parent_id')->index()->nullable()->comment('父级 id');
            $table->string('name')->comment('名称');
            $table->string('cover')->nullable()->comment('封面');
            $table->string('address')->nullable()->comment('地址');
            $table->text('introduction')->nullable()->comment('介绍');
            $table->time('open_time')->nullable()->comment('运营开始时间');
            $table->time('close_time')->nullable()->comment('运营结束时间');
            $table->decimal('longitude', 9, 6)->nullable()->comment('经度');
            $table->decimal('latitude', 9, 6)->nullable()->comment('纬度');
            $table->string('tag')->nullable()->comment('标签');
            $table->string('qrcode')->nullable()->comment('小程序二维码');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('level')->nullable()->comment('层级 1 一级 2 二级 3 三级');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 上线 2 下线');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
