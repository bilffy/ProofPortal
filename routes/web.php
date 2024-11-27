<?php

use App\Helpers\PermissionHelper;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\PhotographyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProofingController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\SchoolList;
use App\Http\Livewire\SchoolView;
use App\Http\Controllers\NavBarController;
use App\Http\Controllers\ImpersonateController;

Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Test pages
    Route::get('/schoolhome', [TestController::class, 'test2'])->name('test2'); // FOR TESTING
    Route::get('/test-photography', [TestController::class, 'index'])->name('test.photography'); // FOR TESTING

    $permissions = PermissionHelper::ACT_CREATE . " " . PermissionHelper::SUB_USER;
    Route::group(['middleware' => ["permission:{$permissions}"]], function () {
        // Users routes
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::get('/users/new', [UserController::class, 'create'])->name('users.create');
        // Route for inviting a single user
        Route::get('/invite/{id}', [InviteController::class, 'inviteSingleUser'])->name('invite.single');
        Route::post('/invite', [InviteController::class, 'inviteMultipleUsers'])->name('invite.multiple');
    });
    
    //Photography
    $permissionCanAccessPhotos = PermissionHelper::ACT_ACCESS . " " . PermissionHelper::SUB_PHOTOGRAPHY;
    Route::group(['middleware' => ["permission:{$permissionCanAccessPhotos}"]], function () {
        Route::get('/photography', [PhotographyController::class, 'index'])->name('photography');
    });
    // Proofing
    $permissionCanProof = PermissionHelper::ACT_ACCESS . " " . PermissionHelper::SUB_PROOFING;
    Route::group(['middleware' => ["permission:{$permissionCanProof}"]], function () {
        Route::get('/proofing', [ProofingController::class, 'index'])->name('proofing');
    });
    
    // Schools routes
    Route::get('/school', SchoolList::class)->name('school.list');
    Route::get('/school/{id}', SchoolView::class)->name('school.view');
    
    // Impersonation routes
    Route::get('/impersonate/as/{id}', [ImpersonateController::class, 'store'])->name('impersonate.store');
    Route::get('/impersonate/leave', [ImpersonateController::class, 'leave'])->name('impersonate.leave');
    
    // Navbar routes
    Route::post('/navbar/toggle-collapse', [NavBarController::class, 'toggleCollapse'])->name('navbar.toggleCollapse');
});


require __DIR__.'/auth.php';
