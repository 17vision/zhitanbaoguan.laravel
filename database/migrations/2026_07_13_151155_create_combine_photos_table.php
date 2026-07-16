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
        Schema::create('combine_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->comment('组织 id');
            $table->unsignedBigInteger('venue_id')->index()->comment('场馆 id');
            $table->unsignedBigInteger('user_id')->index()->comment('用户 id');
            $table->unsignedBigInteger('combine_album_id')->index()->comment('相册分类ID');
            $table->unsignedBigInteger('combine_template_id')->index()->comment('使用的模板ID');
            $table->string('cover')->nullable()->comment('模板原图封面');
            $table->string('photo')->nullable()->comment('用户上传人脸原图');
            $table->string('product_img')->nullable()->comment('AI合成成品图');
            $table->unsignedTinyInteger('status')->default(0)->comment('0待合成 1合成中 2合成成功 3合成失败');
            $table->text('failreason')->nullable()->comment('失败原因');
            $table->date('combine_date')->nullable()->index()->comment('合成日期');
            $table->timestamps();
            // 软删除字段
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combine_photos');
    }
};
