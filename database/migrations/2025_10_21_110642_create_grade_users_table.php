<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 班级成员 
    public function up(): void
    {
        Schema::create('grade_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grade_id')->index()->comment('班级 id');
            $table->unsignedBigInteger('user_id')->index()->comment('用户 id');
            $table->unique(['grade_id', 'user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('grade_id')->references('id')->on('grades')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_users');
    }
};
