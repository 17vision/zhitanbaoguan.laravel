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
use App\Http\Controllers\Api\CourseStatisticsController;
use App\Http\Controllers\Api\UserHomeworkController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\CourseMessageController;
use App\Http\Controllers\Api\GradeUserController;
use App\Http\Controllers\Api\UserBodyMetricController;
use App\Http\Controllers\Api\UserDailyStepController;
use App\Http\Controllers\Api\UserHealthController;
use App\Http\Controllers\Api\BrainMachineDataController;

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

    // 配置
    Route::get('config', [ConfigController::class, 'show']);
});

// 非登录注册相关
// 'request', 去掉验证
// 'user.login' 这个也不要
Route::middleware(['throttle:' . config('api.rate_limits.access'), 'user.get', 'user.login'])->group(function () {

    // 获取课程列表
    Route::get('courses', [CourseController::class, 'index']);

    // 获取课程详情
    Route::get('courses/{id}', [CourseController::class, 'detail'])->where('id', '^[1-9]\d*$');

    // 获取班级信息
    Route::get('grades', [GradeUserController::class, 'grade']);

    // 获取留言列表
    Route::get('course_messages', [CourseMessageController::class, 'index']);

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

        // 获取用户每个月的登录信息
        Route::get('user/logins', [UserController::class, 'logins']);

        // 提交用户的健康情况
        Route::post('user_healths', [UserHealthController::class, 'store']);
        
        // 提交用户身体指标表
        Route::post('user_body_metrics', [UserBodyMetricController::class, 'store']);
        
        // 提交用户每日运动数据
        Route::post('user_daily_steps', [UserDailyStepController::class, 'store']);

        // 喜欢课程
        Route::post('courses/like', [CourseLikeController::class, 'store']);

        // 收藏课程
        Route::post('courses/collect', [CourseCollectController::class, 'store']);

        // 获取作业
        Route::get('homework', [UserHomeworkController::class, 'index']);

        // 获取作业详情
        Route::get('homework/{id}', [UserHomeworkController::class, 'detail'])->where('id', '^[1-9]\d*$');

        Route::post('homework', [UserHomeworkController::class, 'store']);

        // 统计课程
        Route::post('course_statistics', [CourseStatisticsController::class, 'store']);

        // 更新课程统计
        Route::put('course_statistics', [CourseStatisticsController::class, 'update']);

        // 获取统计记录
        Route::get('course_statistics/course_history', [CourseStatisticsController::class, 'courseHistory']);

        Route::get('course_statistics/practise_history', [CourseStatisticsController::class, 'practiseHistory']);

        // 获取自己的班级
        Route::get('grade_users', [GradeUserController::class, 'index']);

        // 加入班级
        Route::post('grade_users', [GradeUserController::class, 'store']);

        // 课程留言

        // 留言
        Route::post('course_messages', [CourseMessageController::class, 'store']);

        // 给留言回复
        Route::post('course_message_replies', [CourseMessageController::class, 'reply']);

        // 给留言点赞(取消点赞)
        Route::post('course_messages/praise', [CourseMessageController::class, 'praise']);

        //  删除留言
        Route::delete('course_messages', [CourseMessageController::class, 'delete']);

        // 脑机数据
        Route::post('brain_machine_data', [BrainMachineDataController::class, 'store']);

        // 获取自己的身高体重等数据
        Route::get('user_body_metrics', [UserController::class, 'getUserBodyMetrics']);
    });
});
