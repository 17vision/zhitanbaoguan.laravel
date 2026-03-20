<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 订单
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index()->comment('用户 id');
            $table->string('device_id')->index()->comment('扫码设备 id');
            $table->string('number', 32)->index()->comment('订单编号');
            $table->string('name')->comment('商品名称');
            $table->decimal('total_amount', 8, 2)->comment('总金额');
            $table->decimal('pay_amount', 8, 2)->comment('付款金额');
            $table->unsignedTinyInteger('payment_type')->comment('支付方式：1 微信 2 支付宝 3 银联 4 余额支付');
            $table->string('payment_number')->nullable()->comment('支付平台单号');
            $table->dateTime('paid_at')->nullable()->comment('支付时间');
            $table->dateTime('refund_at')->nullable()->comment('退款时间');
            $table->dateTime('closed_at')->nullable()->comment('关闭时间');
            $table->dateTime('play_begin_at')->nullable()->comment('体验开始时间');
            $table->dateTime('play_end_at')->nullable()->comment('体验结束时间');
            $table->unsignedTinyInteger('status')->default(1)->comment('支付状态 1 待支付 2 已支付 3 已退款 0 已关闭');
            $table->unsignedTinyInteger('order_status')->default(1)->comment('订单状态 1 待付款 2 待体验 3 体验中 4 已体验 0 已关闭');
            $table->unsignedTinyInteger('refund_status')->nullable()->comment('退款状态 1 退款中 2 已退款');
            $table->unsignedTinyInteger('user_refund_status')->nullable()->comment('退款状态 1 申请中 2 退款中 3 已退款 4 已驳回');
            $table->string('user_refund_reason')->nullable()->comment('用户退款的理由');
            $table->string('refund_reject_reason')->nullable()->comment('用户退款驳回的理由');
            $table->softDeletes();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
