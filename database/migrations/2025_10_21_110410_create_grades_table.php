<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 班级
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->string('cover')->nullable()->comment('封面');
            $table->text('description')->nullable()->comment('描述');
            $table->unsignedMediumInteger('number')->default(0)->comment('班级人数');
            $table->string('qrcode')->nullable()->comment('小程序码');
            $table->string('poster')->nullable()->comment('海报');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('负责人 id');
            $table->datetime('graduation_at')->nullable()->comment('毕业时间');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
