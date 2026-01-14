<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('wx_unionid', 64)->unique()->nullable(); // 微信开放平台唯一id
            $table->string('wxmini_openid', 64)->unique()->nullable(); // 微信小程序openid
            $table->string('wxmini_session_key', 64)->nullable();  // 微信小程序 session_key
            $table->string('wxapp_openid', 64)->unique()->nullable(); // app openid
            $table->string('wxgzh_openid', 64)->unique()->nullable(); // 微信公众号openid
            $table->unsignedInteger('viewid')->unique();
            $table->string('account', 16)->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('nickname', 64)->nullable();
            $table->string('phone_prefix', 8)->default('86')->comment('手机号码前缀');
            $table->string('phone', 16)->nullable();
            $table->unique(['phone', 'phone_prefix']);
            $table->smallInteger('gender')->default(1)->comment('性别 1男，2女');
            $table->string('avatar', 200)->default('static/image/web/avatar.jpg')->comment('头像');
            $table->string('email', 64)->nullable()->unique();
            $table->string('qq', 16)->nullable()->comment('qq');
            $table->string('wechat', 32)->nullable()->comment('微信');
            $table->date('birthday')->nullable()->comment('生日');

            // 新增加
            $table->decimal('height', 4, 1)->nullable()->comment('身高(cm)');
            $table->decimal('weight', 4, 1)->nullable()->comment('体重(kg)');
            $table->unsignedTinyInteger('age')->nullable()->comment('年龄(岁)');
            $table->decimal('body_fat_pct', 3, 1)->nullable()->comment('体质率 22.5%, 填 22.5 就可以');

            $table->unsignedMediumInteger('province')->nullable();
            $table->unsignedMediumInteger('city')->nullable();
            $table->unsignedMediumInteger('town')->nullable();
            $table->string('address')->nullable();
            $table->point('position')->nullable();
            $table->string('signature', 255)->default('美的事物是永恒的喜悦！')->nullable()->comment('签名');
            $table->unsignedTinyInteger('referer')->default(1)->comment('1 账号密码注册 2 批量导入');
            $table->string('territory_ip', 16)->nullable()->comment('ip 属地');
            $table->string('register_ip', 16)->nullable()->comment('注册时ip');
            $table->rememberToken();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
