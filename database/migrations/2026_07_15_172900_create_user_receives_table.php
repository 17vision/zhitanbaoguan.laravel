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
        Schema::create('user_receives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->comment('组织 id');
            $table->unsignedBigInteger('venue_id')->comment('场馆 id');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('用户 id');
            $table->date('date')->index()->comment('日期');
            $table->unsignedInteger('combine_count')->default(0)->comment('合成次数');
            $table->unsignedInteger('explain_count')->default(0)->comment('讲解次数');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_receives');
    }
};
