<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 作业分组
    public function up(): void
    {
        Schema::create('homework_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('创建人');
            $table->string('name');
            $table->string('description')->nullable()->comment('描述');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父 id');
            $table->unsignedSmallInteger('index')->default(0)->comment('排序');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_groups');
    }
};
