<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\web\PageController;

Route::get('/', [PageController::class, 'home'])->name('home');
