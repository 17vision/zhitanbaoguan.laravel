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
        Schema::create('combine_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('combine_album_id')->index()->comment('所属相册分类ID');
            $table->string('name')->comment('模板名称');
            $table->string('cover')->comment('模板封面图');
            $table->text('introduction')->nullable()->comment('模板介绍文案');
            $table->unsignedInteger('sort')->default(0)->comment('排序权重');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 1上线 2下线');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combine_templates');
    }
};
