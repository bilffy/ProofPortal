<?php

use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InviteController;

// Route for inviting a single user
Route::get('/invite/{id}', [InviteController::class, 'inviteSingleUser'])->name('invite.single');
Route::post('/invite', [InviteController::class, 'inviteMultipleUsers'])->name('invite.multiple');
Route::get('/', [TestController::class, 'index']);
Route::inertia('/home', 'Dashboard/Home');
Route::inertia('/test', 'Dashboard/Home');
Route::inertia('/login', 'Auth/Login');
