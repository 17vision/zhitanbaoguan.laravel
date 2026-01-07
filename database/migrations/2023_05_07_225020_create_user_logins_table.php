<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // 登录流水表
    public function up(): void
    {
        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedTinyInteger('client_type')->nullable()->comment('登录端 1 小程序 2 android app 3 ios app');
            $table->string('ip');
            $table->float('latitude', 8, 5)->nullable();
            $table->float('longitude', 8, 5)->nullable();
            $table->string('city')->nullable();
            $table->unsignedInteger('citycode')->nullable();
            $table->dateTime('login_at');
            $table->date('login_date')
              ->virtualAs('DATE(login_at)');
            $table->unique(['user_id', 'login_date'], 'uniq_user_day');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logins');
    }
};
