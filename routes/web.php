<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\web\PageController;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/activity/user', [PageController::class, 'getUser'])->name('get.activity.user');

Route::post('/activity/user', [PageController::class, 'storeUser'])->name('store.activity.user');

