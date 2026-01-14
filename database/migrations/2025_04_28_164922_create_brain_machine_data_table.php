<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 脑机数据
    public function up(): void
    {
        Schema::create('brain_machine_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('concentration_level')->comment('专注度');
            $table->unsignedInteger('relaxation_level')->comment('放松度');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brain_machine_data');
    }
};
