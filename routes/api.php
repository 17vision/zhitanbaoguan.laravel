<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorizationController;
use App\Http\Controllers\Api\EasySmsController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseLikeController;
use App\Http\Controllers\Api\CourseCollectController;
use App\Http\Controllers\Api\UserHomeworkController;

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
// 'request' 去掉验证
Route::middleware(['throttle:' . config('api.rate_limits.sign')])->group(function () {

    // 账号密码登录注册
    Route::post('login/password', [AuthorizationController::class, 'passwordLogin']);

    Route::post('register/password', [AuthorizationController::class, 'passwordRegister']);

    // 发送短信
    Route::post('sms', [EasySmsController::class, 'sendSms']);

    // 小程序登录
    Route::post('wxmini-login', [UserController::class, 'wxminiLogin']);
});

// 非登录注册相关
// 'request', 去掉验证
// 'user.login' 这个也不要
Route::middleware(['throttle:' . config('api.rate_limits.access'), 'user.get'])->group(function () {

    // 获取课程列表
    Route::get('courses', [CourseController::class, 'index']);

    // 获取课程详情
    Route::get('courses/{id}', [CourseController::class, 'detail'])->where('id', '^[1-9]\d*$');

    // 下边需要授权才可以
    Route::middleware(['auth:api'])->group(function () {
        // 小程序授权管理后台登录
        Route::post('admin-login', [AuthorizationController::class, 'adminLogin']);

        // 上传图片
        Route::post('images', [ImageController::class, 'uploadImages']);

        // 上传视频
        Route::post('videos', [VideoController::class, 'uploadVideo']);
        
        // 获取当前用户的信息
        Route::get('userinfos', [UserController::class, 'getUserInfo']);

        // 更新用户信息
        Route::put('userinfos', [UserController::class, 'update']);

        // 喜欢课程
        Route::post('courses/like', [CourseLikeController::class, 'store']);

        // 收藏课程
        Route::post('courses/collect', [CourseCollectController::class, 'store']);

        // 获取作业
        Route::get('homework', [UserHomeworkController::class, 'index']);

        // 获取作业详情
        Route::get('homework/{id}', [UserHomeworkController::class, 'detail'])->where('id', '^[1-9]\d*$');

        Route::post('homework', [UserHomeworkController::class, 'store']);
    });
});
