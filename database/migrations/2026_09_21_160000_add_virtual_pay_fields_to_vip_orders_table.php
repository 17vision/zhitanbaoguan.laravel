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
        Schema::table('vip_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('payment_channel')->default(1)->after('payment_type')
                ->comment('支付渠道：1 普通微信 2 虚拟支付');
            $table->string('product_id', 64)->default('')->after('payment_channel')
                ->comment('虚拟支付道具 ID');
            $table->unsignedTinyInteger('env')->default(0)->after('product_id')
                ->comment('虚拟支付环境：0 现网 1 沙箱');
            $table->unsignedTinyInteger('goods_delivered')->default(0)->after('env')
                ->comment('是否已发货：0 否 1 是');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vip_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_channel', 'product_id', 'env', 'goods_delivered']);
        });
    }
};
