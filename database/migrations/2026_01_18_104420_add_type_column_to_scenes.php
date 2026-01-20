<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            if (!Schema::hasColumn('scenes', 'type')) {
                $table->unsignedTinyInteger('type')->comment('类型 1 专注 2 睡眠 3 小憩 4 呼吸')->after('id');
            }

            if (!Schema::hasColumn('scenes', 'status')) {
                $table->unsignedTinyInteger('status')->default(1)->comment('状态 1 未发布 2 已发布')->after('collect_nums');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            if (Schema::hasColumn('scenes', 'type')) {
                Schema::dropColumns('scenes', 'type');
            }

            if (Schema::hasColumn('scenes', 'status')) {
                Schema::dropColumns('scenes', 'status');
            }
        });
    }
};
