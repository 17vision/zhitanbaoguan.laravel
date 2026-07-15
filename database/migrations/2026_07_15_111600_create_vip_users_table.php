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
        Schema::create('vip_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->comment('组织 id');
            $table->unsignedBigInteger('venue_id')->comment('场馆 id');
            $table->unsignedBigInteger('user_id')->comment('用户 id');
            $table->unsignedInteger('combine_count')->default(0)->comment('合成照片次数');
            $table->dateTime('expired_at')->nullable()->comment('会员到期时间');
            $table->timestamps();

            $table->unique(['user_id', 'venue_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vip_users');
    }
};
