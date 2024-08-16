<?php

use App\Http\Controllers\InviteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/test', [TestController::class, 'index']); // FOR TESTING ONLY; DELETE WHEN DEPLOYING
Route::inertia('/setup', 'Auth/AccountSetup');
Route::inertia('/verification', 'Auth/Verification');
Route::inertia('/dashboard', 'Dashboard/Home');

Route::get('/', function () {
    return Inertia::render('App');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Route for inviting a single user
    Route::get('/invite/{id}', [InviteController::class, 'inviteSingleUser'])->name('invite.single');
    Route::post('/invite', [InviteController::class, 'inviteMultipleUsers'])->name('invite.multiple');
    // Users routes
    Route::get('/users', [UserController::class, 'index'])->name('users.manage');
    Route::get('/users/new', [UserController::class, 'create'])->name('users.create');

    Route::get('/proofing', [TestController::class, 'index'])->name('proofing'); // FOR TESTING
});

require __DIR__.'/auth.php';
