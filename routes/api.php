<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorizationController;
use App\Http\Controllers\Api\EasySmsController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PayController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\CombineAlbumController;
use App\Http\Controllers\Api\CombineTemplateController;
use App\Http\Controllers\Api\VipPackageController;
use App\Http\Controllers\Api\VipOrderController;
use App\Http\Controllers\Api\CombinePhotoController;
use App\Http\Controllers\Api\VenueController;

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

    // app 微信授权登录
    Route::post('wxapp-login', [UserController::class, 'wxappLogin']);
});

// 非登录注册相关
// 'request', 去掉验证
// 'user.login' 这个也不要
Route::middleware(['throttle:' . config('api.rate_limits.access'), 'user.get', 'user.login'])->group(function () {

    Route::get('venues/{id}', [VenueController::class, 'detail'])->where('id', '^[1-9]\d*$');

    // 合成图片用专辑
    Route::get('combine_albums', [CombineAlbumController::class, 'index']);
    Route::get('combine_albums/{id}', [CombineAlbumController::class, 'detail'])->where('id', '^[1-9]\d*$');
    // 合成图片用模板
    Route::get('combine_templates/{id}', [CombineTemplateController::class, 'detail'])->where('id', '^[1-9]\d*$');

    Route::get('places', [PlaceController::class, 'index']);

    Route::get('places/{id}', [PlaceController::class, 'detail'])->where('id', '^[1-9]\d*$');

    Route::get('vippackages', [VipPackageController::class, 'index']);

    // 下边需要授权才可以
    Route::middleware(['auth:api'])->group(function () {
        // 小程序授权管理后台登录
        Route::post('admin-login', [AuthorizationController::class, 'adminLogin']);

        // 上传图片
        Route::post('images', [ImageController::class, 'uploadImages']);

        // 获取当前用户的信息
        Route::get('userinfos', [UserController::class, 'getUserInfo']);

        // 更新用户信息
        Route::put('userinfos', [UserController::class, 'update']);

        // 获取用户每个月的登录信息
        Route::get('user/logins', [UserController::class, 'logins']);

        // 被允许的操作行为
        Route::get('permitted-action', [UserController::class, 'getPermittedAction']);

        // 用户合成图片列表
        Route::get('combine_photos', [CombinePhotoController::class, 'index']);

        // 获取自己的身高体重等数据
        Route::get('user_body_metrics', action: [UserController::class, 'getUserBodyMetrics']);

        // 获取自己的运动数据
        Route::get('user_daily_steps',[UserController::class, 'getUserDailySteps']);

        // 微信支付(测试)
        Route::post('pay/payment', [PayController::class, 'payment']);

        Route::post('pay/payment/status', [PayController::class, 'paymentStatus']);

        // vip订单列表
        Route::get('vip/orders', [VipOrderController::class, 'index']);
        // 查看 vip 订单状态
        Route::post('vip/payments/status', [VipOrderController::class, 'paymentStatus']);
        // 取消 vip 订单
        Route::post('vip/orders/cancel', [VipOrderController::class, 'cancel']);
        // 发起 vip 订单退款
        Route::post('vip/orders/refund', [VipOrderController::class, 'refund']);
        // vip 快捷支付
        Route::post('vip/payments/quick', [VipOrderController::class, 'quickPayment']);
    });
});


// 微信支付通知
Route::post('pay/wechat-notify', [PayController::class, 'wechatNotify']);

// unity 获取已支付的订单
Route::get('workflows/paid_orders', [OrderController::class, 'paidOrders']);

// 更改状态
Route::put('workflows/paid_orders', [OrderController::class, 'updateOrders']);

// 发起退款
Route::post('workflows/paid_orders/refund', [OrderController::class, 'refundOrders']);
