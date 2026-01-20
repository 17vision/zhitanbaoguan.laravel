<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AuthorizationController;
use App\Http\Controllers\Admin\CaptchaController;
use App\Http\Controllers\Admin\CourseanalysisController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\FileController;
use App\Http\Controllers\Admin\CourseChapterController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\GradeUserController;
use App\Http\Controllers\Admin\HomeworkanalysisController;
use App\Http\Controllers\Admin\BrainMachineDataController;
use App\Http\Controllers\Admin\DailySentenceController;
use App\Http\Controllers\Admin\HomeworkController;
use App\Http\Controllers\Admin\HomeworkGroupController;
use App\Http\Controllers\Admin\UserHomeworkController;
use App\Http\Controllers\Admin\TutorController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\ResourceGroupController;
use App\Http\Controllers\Admin\RingtoneController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\SceneController;
use App\Http\Controllers\Admin\SceneCategoryController;

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

                Route::put('course_chapters', [CourseChapterController::class, 'update'])->middleware('permission:course.update');

                Route::delete('course_chapters', [CourseChapterController::class, 'delete'])->middleware('permission:course.delete');

                // 获取课程留言
                Route::get('courses/messages', [CourseController::class, 'getMessages'])->middleware('permission:course.detail');

                // 删除课程留言
                Route::delete('courses/messages', [CourseController::class, 'deleteMessages'])->middleware('permission:course.detail');
            });

            // 导师管理
            Route::group(['middleware' => 'permission:tutor'], function () {

                Route::get('tutors', [TutorController::class, 'index'])->middleware('permission:tutor.index');

                Route::post('tutors', [TutorController::class, 'store'])->middleware('permission:tutor.create');

                Route::put('tutors', [TutorController::class, 'update'])->middleware('permission:tutor.create');

                Route::delete('tutors', [TutorController::class, 'delete'])->middleware('permission:tutor.delete');
            });


            // 作业管理
            Route::group(['middleware' => 'permission:homework'], function () {
                // 作业
                Route::get('homework', [HomeworkController::class, 'index'])->middleware('permission:homework.index');

                Route::get('homework/{id}', [HomeworkController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:homework.detail');

                Route::post('homework', [HomeworkController::class, 'store'])->middleware('permission:homework.create');

                Route::put('homework', [HomeworkController::class, 'update'])->middleware('permission:homework.create');

                Route::delete('homework', [HomeworkController::class, 'delete'])->middleware('permission:homework.delete');

                // 作业分组
                Route::get('homework_groups', [HomeworkGroupController::class, 'index'])->middleware('permission:homework_group.index');

                Route::post('homework_groups', [HomeworkGroupController::class, 'store'])->middleware('permission:homework_group.create');

                Route::put('homework_groups', [HomeworkGroupController::class, 'update'])->middleware('permission:homework_group.create');

                Route::delete('homework_groups', [HomeworkGroupController::class, 'delete'])->middleware('permission:homework_group.delete');

                // 给某个班级或某个人分配作业
                Route::get('user_homework', [UserHomeworkController::class, 'index'])->middleware('permission:homework.create');

                Route::post('user_homework', [UserHomeworkController::class, 'store'])->middleware('permission:homework.create');

                // 删除某人的作业
                Route::delete('user_homework', [UserHomeworkController::class, 'delete'])->middleware('permission:homework.delete');
            });

            // 班级管理
            Route::group(['middleware' => 'permission:grade'], function () {
                // 班级
                Route::get('grades', [GradeController::class, 'index'])->middleware('permission:grade.index');

                Route::get('grades/{id}', [GradeController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:grade.detail');

                Route::post('grades', [GradeController::class, 'store'])->middleware('permission:grade.create');

                Route::put('grades', [GradeController::class, 'update'])->middleware('permission:grade.create');

                Route::delete('grades', [GradeController::class, 'delete'])->middleware('permission:grade.delete');

                // 班级用户
                Route::get('grade_users', [GradeUserController::class, 'index'])->middleware('permission:grade_user.index');

                Route::get('grade_users/{id}', [GradeUserController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:grade_user.index');

                Route::post('grade_users', [GradeUserController::class, 'store'])->middleware('permission:grade_user.create');

                Route::delete('grade_users', [GradeUserController::class, 'delete'])->middleware('permission:grade_user.delete');
            });

            // 资源管理
            Route::group(['middleware' => 'permission:resource'], function () {

                Route::get('resources', [ResourceController::class, 'index'])->middleware('permission:resource.index');

                Route::post('resources', [ResourceController::class, 'store'])->middleware('permission:resource.create');

                Route::put('resources', [ResourceController::class, 'update'])->middleware('permission:resource.create');

                Route::delete('resources', [ResourceController::class, 'delete'])->middleware('permission:resource.delete');
            });

            // 资源分组管理
            Route::group(['middleware' => 'permission:resource_group'], function () {

                Route::get('resource_groups', [ResourceGroupController::class, 'index'])->middleware('permission:resource_group.index');

                Route::post('resource_groups', [ResourceGroupController::class, 'store'])->middleware('permission:resource_group.create');

                Route::put('resource_groups', [ResourceGroupController::class, 'update'])->middleware('permission:resource_group.create');

                Route::delete('resource_groups', [ResourceGroupController::class, 'delete'])->middleware('permission:resource_group.delete');
            });

            // 主题
            Route::group(['middleware' => 'permission:theme'], function () {

                Route::get('themes', [ThemeController::class, 'index'])->middleware('permission:theme.index');

                Route::post('themes', [ThemeController::class, 'store'])->middleware('permission:theme.create');

                Route::put('themes', [ThemeController::class, 'update'])->middleware('permission:theme.create');

                Route::delete('themes', [ThemeController::class, 'delete'])->middleware('permission:theme.delete');
            });

            // 铃声
            Route::group(['middleware' => 'permission:ringtone'], function () {

                Route::get('ringtones', [RingtoneController::class, 'index'])->middleware('permission:ringtone.index');

                Route::post('ringtones', [RingtoneController::class, 'store'])->middleware('permission:ringtone.create');

                Route::put('ringtones', [RingtoneController::class, 'update'])->middleware('permission:ringtone.create');

                Route::delete('ringtones', [RingtoneController::class, 'delete'])->middleware('permission:ringtone.delete');
            });

            // 场景
            Route::group(['middleware' => 'permission:scene'], function () {

                Route::get('scenes', [SceneController::class, 'index'])->middleware('permission:scene.index');

                Route::get('scenes/{id}', [SceneController::class, 'detail'])->where('id', '^[1-9]\d*$')->middleware('permission:scene.index');

                Route::post('scenes', [SceneController::class, 'store'])->middleware('permission:scene.create');

                Route::put('scenes', [SceneController::class, 'update'])->middleware('permission:scene.create');

                Route::delete('scenes', [SceneController::class, 'delete'])->middleware('permission:scene.delete');

                // 主题分组
                Route::get('scene_categories', [SceneCategoryController::class, 'index'])->middleware('permission:scene.index');

                Route::post('scene_categories', [SceneCategoryController::class, 'store'])->middleware('permission:scene.create');

                Route::put('scene_categories', [SceneCategoryController::class, 'update'])->middleware('permission:scene.create');

                Route::delete('scene_categories', [SceneCategoryController::class, 'delete'])->middleware('permission:scene.delete');
            });
        });

        // todo 课程分析，应该放在对应的权限里
        
        // 课程分析
        Route::get('datastatistics/courseanalysis/basic', [CourseanalysisController::class, 'basic']);

        Route::get('datastatistics/courseanalysis/view', [CourseanalysisController::class, 'view']);

        
        // 作业分析
        Route::get('datastatistics/homeworkanalysis/basic', [HomeworkanalysisController::class, 'basic']);

        Route::get('datastatistics/homeworkanalysis/view', [HomeworkanalysisController::class, 'view']);

        // 获取脑机接口数据
        Route::get('brain_machine_data', [BrainMachineDataController::class, 'index']);

        // 每日一句
        Route::get('daily_sentences', [DailySentenceController::class, 'index']);

        Route::get('daily_sentences/{id}', [DailySentenceController::class, 'detail'])->where('id', '^[1-9]\d*$');

        Route::post('daily_sentences', [DailySentenceController::class, 'store']);

        Route::put('daily_sentences', [DailySentenceController::class, 'update']);

        Route::delete('daily_sentences', [DailySentenceController::class, 'delete']);
    });
});
