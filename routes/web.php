<?php

use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TestController::class, 'index']);
Route::inertia('/home', 'Dashboard/Home');
Route::inertia('/test', 'Dashboard/Home');
Route::inertia('/login', 'Auth/Login');
