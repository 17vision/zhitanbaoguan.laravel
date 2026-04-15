<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthorizationController;
use App\Http\Controllers\Admin\CaptchaController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\VenueController;
use App\Http\Controllers\Admin\PlaceController;

Route::middleware(['throttle:' . config('api.rate_limits.sign')])->group(function () {
    // 获取验证码
    Route::post('captchas', [CaptchaController::class, 'getCaptcha']);

    // 登录
    Route::post('login', [AuthorizationController::class, 'login']);

    // 注册
    Route::post('register', [AuthorizationController::class, 'register']);

    // 获取登录小程序码
    Route::post('login-qrcode', [AuthorizationController::class, 'getQrcode']);

    // 通过 key 获取 token
    Route::get('login-token', [AuthorizationController::class, 'getToken']);

    // 小程序授权管理后台登录(临时的，暂时小程序是 vue2 版本才需要抓接)
    Route::post('admin-login', [AuthorizationController::class, 'adminLogin']);

    Route::group([
        'middleware' => ['auth:admin'],
    ], function () {
        Route::get('user/info', [AuthorizationController::class, 'getUserInfo']);

        Route::put('user/roles', [AuthorizationController::class, 'setRole']);

        Route::put('user/passwords', [AuthorizationController::class, 'setPassword']);

        Route::get('user/profile', [AuthorizationController::class, 'getUserProfile']);

        Route::put('user/profile', [AuthorizationController::class, 'setUserProfile']);

        // 设置锁屏密码
        Route::put('user/lock', [AuthorizationController::class, 'setLock']);

        // 检查锁屏密码
        Route::post('user/lock', [AuthorizationController::class, 'checkLock']);

        Route::post('images', [ImageController::class, 'store']);

        Route::post('files', [FileController::class, 'store']);

        // 仪表盘统计
        Route::get('dashboard/basic_info', [DashboardController::class, 'basicInfo']);

        Route::get('dashboard/single_data', [DashboardController::class, 'singleData']);

        Route::get('dashboard/view_data', [DashboardController::class, 'viewData']);

        Route::group(['middleware' => 'permission:system'], function () {
            // 权限管理（删除权限，角色和用户拥有的权限会自动删除)
            Route::group(['middleware' => 'permission:permission'], function () {
                Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:permission.index');

                Route::get('permissions/{id}', [PermissionController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:permission.edit');

                Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:permission.create|permission.edit');

                Route::delete('permissions', [PermissionController::class, 'delete'])->middleware('permission:permission.delete');

                Route::put('permissions/orders', [PermissionController::class, 'updateOrders'])->middleware('permission:permission.edit');
            });

            // 角色管理
            Route::group(['middleware' => 'permission:role'], function () {
                Route::get('roles', [RoleController::class, 'index'])->middleware('permission:role.index');

                Route::get('roles/{id}', [RoleController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:role.edit');

                Route::post('roles', [RoleController::class, 'store'])->middleware('permission:role.create|role.edit');

                Route::delete('roles', [RoleController::class, 'delete'])->middleware('permission:role.delete');

                Route::get('roles/permissions', [RoleController::class, 'getPermissions'])->middleware('permission:role.permission');

                Route::put('roles/permissions', [RoleController::class, 'setPermission'])->middleware('permission:role.permission');
            });

            // 用户管理
            Route::group(['middleware' => 'permission:user'], function () {
                Route::get('users', [UserController::class, 'index'])->middleware('permission:user.index');

                Route::get('users/{id}', [UserController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:user.edit');

                Route::put('users', [UserController::class, 'update'])->middleware('permission:user.edit');

                Route::get('users/roles', [UserController::class, 'getUserRoles'])->middleware('permission:user.role');

                Route::put('users/roles', [UserController::class, 'setUserRoles'])->middleware('permission:user.role');

                Route::post('users/imports', [UserController::class, 'importUser'])->middleware('permission:user.edit');
            });
        });

        // 业务管理
        Route::group(['middleware' => 'permission:business'], function () {

            // 组织管理（整个系统都是面向组织的）
            Route::group(['middleware' => 'permission:organization'], function () {
                Route::get('organizations', [OrganizationController::class, 'index'])->middleware('permission:organization.index');

                Route::get('organizations/{id}', [OrganizationController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:organization.create|organization.edit');

                Route::post('organizations', [OrganizationController::class, 'store'])->middleware('permission:organization.create|organization.edit');

                Route::put('organizations', [OrganizationController::class, 'update'])->middleware('permission:organization.create|organization.edit');

                Route::delete('organizations', [OrganizationController::class, 'delete'])->middleware('permission:organization.delete');
            });

            // 场馆管理
            Route::group(['middleware' => 'permission:venue'], function () {
                Route::get('venues', [VenueController::class, 'index'])->middleware('permission:venue.index');

                Route::get('venues/{id}', [VenueController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:venue.create|venue.edit');

                Route::post('venues', [VenueController::class, 'store'])->middleware('permission:venue.create|venue.edit');

                Route::put('venues', [VenueController::class, 'update'])->middleware('permission:venue.create|venue.edit');

                Route::delete('venues', [VenueController::class, 'delete'])->middleware('permission:venue.delete');
            });

            // 点位管理
            Route::group(['middleware' => 'permission:place'], function () {
                Route::get('places', [PlaceController::class, 'index'])->middleware('permission:place.index');

                Route::get('places/{id}', [PlaceController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:place.create|place.edit');

                Route::post('places', [PlaceController::class, 'store'])->middleware('permission:place.create|place.edit');

                Route::put('places', [PlaceController::class, 'update'])->middleware('permission:place.create|place.edit');

                Route::delete('places', [PlaceController::class, 'delete'])->middleware('permission:place.delete');
            });
        });
    });
});
