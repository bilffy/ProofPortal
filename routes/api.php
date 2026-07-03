<?php

use App\Http\Controllers\Api\SessionController;
use App\Http\Middleware\NoCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', NoCacheHeaders::class])->group(function () {
    Route::get('ping', [SessionController::class, 'ping'])->name(name: 'session.ping');
});
