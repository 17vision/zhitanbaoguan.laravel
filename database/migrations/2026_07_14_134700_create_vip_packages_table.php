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
        Schema::create('vip_packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_name', 100)->comment('套餐名称');
            $table->text('description')->nullable()->comment('套餐详细描述');
            $table->decimal('price', 10, 2)->comment('实际售价');
            $table->decimal('original_price', 10, 2)->comment('原价（划线价）');
            $table->unsignedTinyInteger('is_recommend')->default(0)->comment('是否推荐套餐 0=否 1=是');
            $table->unsignedTinyInteger('is_only_once')->default(0)->comment('是否只能购买一次 0=否 1=是');
            $table->unsignedInteger('combine_count')->default(0)->comment('合成照片次数');
            $table->unsignedTinyInteger('chinese_explain')->default(0)->comment('中文讲解');
            $table->unsignedTinyInteger('multi_explain')->default(0)->comment('多语言讲解');
            $table->integer('sort')->default(0)->comment('排序权重');
            $table->unsignedTinyInteger('status')->default(0)->comment('套餐状态 0=待上架 1=上架 2=下架');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vip_packages');
    }
};
