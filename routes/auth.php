<?php

use App\Livewire\Auth\AccountSetup;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\OtpVerification;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::get('account-setup/{token}/{email}', AccountSetup::class)->name('account.setup.create');
    Route::get('/otp/{token}', OtpVerification::class)->name('otp.show.form');
});

Route::middleware('auth')->group(function () {
    Route::match(['get', 'post'],'logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
