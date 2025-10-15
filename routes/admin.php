<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AuthorizationController;
use App\Http\Controllers\Admin\CaptchaController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\CourseChapterController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\TutorController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\ResourceGroupController;

Route::get('reset', function (Request $request) {
    defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));

    Artisan::call('db:seed --class=PermissionSeeder --force');

    return 'reset success';
});

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
            // 课程管理
            Route::group(['middleware' => 'permission:course'], function () {
                // 课程
                Route::get('courses', [CourseController::class, 'index'])->middleware('permission:course.index');

                Route::get('courses/{id}', [CourseController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:course.detail');

                Route::post('courses', [CourseController::class, 'store'])->middleware('permission:course.create');

                Route::put('courses', [CourseController::class, 'update'])->middleware('permission:course.update');

                Route::delete('courses', [RoleController::class, 'delete'])->middleware('permission:course.delete');

                // 课程章节
                Route::get('course_chapters', [CourseChapterController::class, 'index'])->middleware('permission:course.index');

                Route::get('course_chapters/{id}', [CourseChapterController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:course.detail');

                Route::post('course_chapters', [CourseChapterController::class, 'store'])->middleware('permission:course.create');

                Route::put('course_chapters', [CourseController::class, 'update'])->middleware('permission:course.update');

                Route::delete('course_chapters', [CourseChapterController::class, 'delete'])->middleware('permission:course.delete');
            });

            // 导师管理
            Route::group(['middleware' => 'permission:tutor'], function () {

                Route::get('tutors', [TutorController::class, 'index'])->middleware('permission:tutor.index');

                Route::post('tutors', [TutorController::class, 'store'])->middleware('permission:tutor.create');

                Route::put('tutors', [TutorController::class, 'update'])->middleware('permission:tutor.create');

                Route::delete('tutors', [TutorController::class, 'delete'])->middleware('permission:tutor.delete');
            });

            // 资源管理
            Route::group(['middleware' => 'permission:resource'], function () {

                Route::get('resources', [ResourceController::class, 'index'])->middleware('permission:resources.index');

                Route::post('resources', [ResourceController::class, 'store'])->middleware('permission:resources.create');

                Route::put('resources', [ResourceController::class, 'update'])->middleware('permission:resources.create');

                Route::delete('resources', [ResourceController::class, 'delete'])->middleware('permission:resources.delete');
            });

            // 分组管理
            Route::group(['middleware' => 'permission:resource_group'], function () {

                Route::get('resource_groups', [ResourceGroupController::class, 'index'])->middleware('permission:resource_group.index');

                Route::post('resource_groups', [ResourceGroupController::class, 'store'])->middleware('permission:resource_group.create');

                Route::put('resource_groups', [ResourceGroupController::class, 'update'])->middleware('permission:resource_group.create');

                Route::delete('resource_groups', [ResourceGroupController::class, 'delete'])->middleware('permission:resource_group.delete');
            });
        });
    });
});
