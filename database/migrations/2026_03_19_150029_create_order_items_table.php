<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 订单列表
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index()->comment('课程 id');
            $table->unsignedBigInteger('workflow_id')->index()->comment('课程 id');
            $table->decimal('pay_amount', 8, 2)->comment('付款金额');
            $table->dateTime('play_begin_at')->nullable()->comment('体验开始时间');
            $table->dateTime('play_end_at')->nullable()->comment('体验结束时间');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
