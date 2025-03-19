<?php

use App\Http\Controllers\Api\SessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('ping', [SessionController::class, 'ping'])->name(name: 'session.ping');
});
