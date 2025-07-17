<?php

use App\Helpers\EncryptionHelper;
use App\Helpers\PermissionHelper;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisableUserController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\NavBarController;
use App\Http\Controllers\PhotographyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Proofing\ConfigureController;
use App\Http\Controllers\ProofingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Livewire\Order\Order;
use App\Http\Livewire\SchoolList;
use App\Http\Livewire\SchoolView;
use App\Http\Livewire\Settings\FeatureControl;
use App\Http\Livewire\Settings\RolePermission;
use App\Http\Middleware\CheckUserRestriction;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/tokens/create', function (Request $request) {
        $user = Auth::user();
        $token = $user->createToken('api_token');
        // return response()->json(['token' => $token->plainTextToken, 'id' => EncryptionHelper::simpleEncrypt($user->id, nonce: $token)]);
        return response()->json(['token' => $token->plainTextToken, 'id' => $user->id]);
    });
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Profile API routes
    Route::get('profile/edit', [UserController::class, 'edit'])->name(name: 'api.profile.edit');
    Route::patch('profile/update', [UserController::class, 'update'])->name(name: 'api.profile.update');
    
    // Test pages
    // Route::get('/schoolhome', [TestController::class, 'test2'])->name('test2'); // FOR TESTING
    // Route::get('/test-photography', [TestController::class, 'index'])->name('test.photography'); // FOR TESTING

    $permissions = PermissionHelper::ACT_CREATE . " " . PermissionHelper::SUB_USER;
    Route::group(['middleware' => ["permission:{$permissions}"]], function () {
        // Users routes
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::get('/users/new', [UserController::class, 'create'])->name('users.create');
        // Route for inviting a single user
        Route::get('/invite/{id}', [InviteController::class, 'inviteSingleUser'])->name('invite.single');
        Route::post('/invite', [InviteController::class, 'inviteMultipleUsers'])->name('invite.multiple');
        Route::get('/invite/check-user-status/{id}', [InviteController::class, 'checkUserStatus'])->name('invite.check-user-status');
    });
    
    //Photography
    $permissionCanAccessPhotos = PermissionHelper::ACT_ACCESS . " " . PermissionHelper::SUB_PHOTOGRAPHY;
    Route::group(['middleware' => ["permission:{$permissionCanAccessPhotos}", CheckUserRestriction::class]], function () {
        Route::get('/photography', [PhotographyController::class, 'index'])->name('photography');
        // Route::get('/photography/configure', [PhotographyController::class, 'showConfiguration'])->middleware(['role:Franchise'])->name('photography.configure');
        Route::get('/photography/configure', [PhotographyController::class, 'showConfiguration'])->middleware(['role:Franchise'])->name('photography.configure-new');
        Route::get('/photography/portraits', [PhotographyController::class, 'showPortraits'])->name('photography.portraits');
        Route::get('/photography/groups', [PhotographyController::class, 'showGroups'])->name('photography.groups');
        Route::get('/photography/others', [PhotographyController::class, 'showOthers'])->name('photography.others');
        Route::post('/photography/request-download', [PhotographyController::class, 'requestDownloadDetails'])->name('photography.request-download');
        Route::post('/photography/request-download-nonce', [PhotographyController::class, 'execNonce'])->name('photography.request-download-nonce');
    });
    // Proofing
    $permissionCanProof = PermissionHelper::ACT_ACCESS . " " . PermissionHelper::SUB_PROOFING;
    Route::group(['middleware' => ["permission:{$permissionCanProof}"]], function () {
        Route::get('/proofing', [ProofingController::class, 'index'])->name('proofing');
    });
    
    // Schools routes
    Route::get('/school', SchoolList::class)->name('school.list');
    Route::get('/school/{hashedId}', SchoolView::class)->name('school.view');
    
    // Order routes
    Route::get('/order', Order::class)->name('order');
    
    // Impersonation routes
    Route::get('/impersonate/as/{id}', [ImpersonateController::class, 'store'])->name('impersonate.store');
    Route::get('/impersonate/leave', [ImpersonateController::class, 'leave'])->name('impersonate.leave');

    // Impersonation routes
    Route::get('/disable/{id}', [DisableUserController::class, 'disable'])->name('disable.user');
    
    // Navbar routes
    Route::post('/navbar/toggle-collapse', [NavBarController::class, 'toggleCollapse'])->name('navbar.toggleCollapse');

    // SETTINGS
    Route::get('/settings', [SettingsController::class, 'main'])->name('settings.main');
    Route::get('/settings/feature-control', FeatureControl::class)->name('settings.feature.control');
    Route::get('/settings/role-permission', RolePermission::class)->name('settings.role.permission');
    
    //Configure School - fetch jobs by season
        Route::get('/config-school/fetch-jobs', [ConfigureController::class, 'configSchoolFetchJobs'])->name('config-school-fetch-jobs');
    //Configure School - get-job-details of job
        Route::post('/config-school/folder-config', [ConfigureController::class, 'configSchoolFolderConfig'])->name('config-school-folder-config');
    //Configure School - Submit
        Route::post('/config-school/digital-download/submit', [ConfigureController::class, 'configSchoolDigitalDownload'])->name('config-school-digital-download');
    //Configure School - Job Change Submit
        Route::post('/job-change/submit', [ConfigureController::class, 'configSchoolJobChangeUpdate'])->name('config-school-job-change-update');
    //Configure School - Folder Change Submit
        Route::post('/folder-change/submit', [ConfigureController::class, 'configSchoolFolderChangeUpdate'])->name('config-school-folder-change-update');
    //Configure School - School Submit
        Route::post('/school-change/submit', [ConfigureController::class, 'configSchoolChangeUpdate'])->name('config-school-change-update');
    //Configure School - School Logo Upload
        Route::post('/config-school/upload-school-logo', [ConfigureController::class, 'uploadSchoolLogo'])->name('upload.school.logo');
    //Configure School - show encrypted image path
        Route::get('/config-school/school-logo/{encryptedPath}', [ConfigureController::class, 'showSchoolLogo'])->name('school.logo');
    //Configure School - School Logo Delete
        Route::post('/config-school/delete-school-logo', [ConfigureController::class, 'deleteSchoolLogo'])->name('delete.school.logo');
    //Configure School - view
        // Route::get('/config-school', [ConfigureController::class, 'configSchool'])->name('config-school');
});

// Livewire::setUpdateRoute(function ($handle) {
//     return Route::post('/livewire/update', $handle)
//         ->middleware(ThrottleRequests::class);
// });


require __DIR__.'/auth.php';
