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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('combine_count')->default(0)->comment('合成照片次数')->after('body_fat_pct');
            $table->unsignedTinyInteger('chinese_explain')->default(0)->comment('中文讲解')->after('combine_count');
            $table->unsignedTinyInteger('multi_explain')->default(0)->comment('多语言讲解')->after('chinese_explain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['combine_count', 'chinese_explain', 'multi_explain']);
        });
    }
};
