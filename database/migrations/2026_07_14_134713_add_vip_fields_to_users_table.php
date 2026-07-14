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
            $table->dateTime('chinese_explain_expire')->nullable()->comment('中文讲解有效期')->after('combine_count');
            $table->dateTime('multi_explain_expire')->nullable()->comment('多语言讲解有效期')->after('chinese_explain_expire');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['combine_count', 'chinese_explain_expire', 'multi_explain_expire']);
        });
    }
};
