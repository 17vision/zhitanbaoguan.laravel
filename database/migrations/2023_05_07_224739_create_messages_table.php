<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 消息表
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('title')->nullable();
            $table->text('content')->comment('消息内容');
            $table->unsignedBigInteger('messageable_id')->nullable()->comment('父模型的 id');
            $table->string('messageable_type')->nullable()->comment('父模型的类名');
            $table->boolean('status')->default(0)->comment('0 未读 1 已读');
            $table->dateTime('readed_at')->nullable()->comment('已读消息时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
