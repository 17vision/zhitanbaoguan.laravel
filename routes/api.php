<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorizationController;
use App\Http\Controllers\Api\EasySmsController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\VideoController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// 登录注册相关
Route::middleware(['request', 'throttle:' . config('api.rate_limits.sign')])->group(function () {

    // 账号密码登录注册
    Route::post('login/password', [AuthorizationController::class, 'passwordLogin']);

    Route::post('register/password', [AuthorizationController::class, 'passwordRegister']);

    // 发送短信
    Route::post('sms', [EasySmsController::class, 'sendSms']);
});

// 非登录注册相关
Route::middleware(['request', 'throttle:' . config('api.rate_limits.access'), 'user.get'])->group(function () {

    // 下边需要授权才可以
    Route::middleware(['auth:api', 'user.login'])->group(function () {
        // 小程序授权管理后台登录
        Route::post('admin-login', [AuthorizationController::class, 'adminLogin']);

        // 上传图片
        Route::post('images', [ImageController::class, 'uploadImages']);

        // 上传视频
        Route::post('videos', [VideoController::class, 'uploadVideo']);
    });
});
