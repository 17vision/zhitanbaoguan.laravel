<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 场馆表
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index()->comment('组织 id');
            $table->string('name');
            $table->string('cover')->nullable()->comment('封面');
            $table->string('address')->nullable()->comment('地址');
            $table->string('phone')->nullable()->comment('联系方式');
            $table->string('introduction')->nullable()->comment('介绍');
            $table->dateTime('open_at')->nullable()->comment('运营开始时间');
            $table->dateTime('close_at')->nullable()->comment('运营结束时间');
            $table->decimal('longitude', 9, 6)->nullable()->comment('经度');
            $table->decimal('latitude', 9, 6)->nullable()->comment('纬度');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 上线 2 下线');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
