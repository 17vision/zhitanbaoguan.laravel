<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 资源分组
    public function up(): void
    {
        Schema::create('resource_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('创建人');
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父 id');
            $table->unsignedInteger('count')->default(0)->comment('直系文件夹下资源数目');
            $table->unsignedSmallInteger('index')->default(0)->comment('排序');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_groups');
    }
};
