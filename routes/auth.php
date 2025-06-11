<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\UserController;
use App\Http\Livewire\Auth\AccountSetup;
use App\Http\Livewire\Auth\ForgotPassword;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Auth\OtpVerification;
use App\Http\Livewire\Auth\ResetPassword;
use App\Http\Livewire\Profile\ResetMyPassword;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('reset-password/{token}/{email}', ResetPassword::class)->name('password.reset');
    Route::get('account-setup/{token}/{email}', AccountSetup::class)->name('account.setup.create');
    Route::get('/otp/{token}', OtpVerification::class)->name('otp.show.form');
});

Route::middleware('auth')->group(function () {
    Route::post('register', [UserController::class, 'store'])->name('user.register');
    Route::get('/reset-password', ResetMyPassword::class)->name('reset.my.password');
    Route::match(['get', 'post'],'logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
