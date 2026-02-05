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
use App\Http\Controllers\ProofingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Livewire\Order\Order;
use App\Http\Livewire\SchoolList;
use App\Http\Livewire\SchoolView;
use App\Http\Livewire\Settings\FeatureControl;
use App\Http\Livewire\Settings\RolePermission;
use App\Http\Middleware\CheckUserRestriction;
use App\Http\Middleware\NoCacheHeaders;
use App\Http\Middleware\CheckJobSession;
use Illuminate\Support\Facades\Route;

//Proofing
use App\Http\Controllers\Proofing\ProofHomeController;
use App\Http\Controllers\Proofing\ConfigureController;
use App\Http\Controllers\Proofing\ProofController;
use App\Http\Controllers\Proofing\ReviewStatusController;
use App\Http\Controllers\Proofing\InvitationController;
use App\Http\Controllers\Proofing\ImageController;
use App\Http\Controllers\Proofing\ReportController;
use App\Http\Controllers\Proofing\SubjectChangesController;
use App\Http\Controllers\Proofing\ConstantsController;

Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified', NoCacheHeaders::class])->name('dashboard');

Route::middleware(['auth', NoCacheHeaders::class])->group(function () {
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
    Route::get('profile/edit', [UserController::class, 'edit'])->name('api.profile.edit');
    Route::patch('profile/update', [UserController::class, 'update'])->name('api.profile.update');
    
    // Account Creation
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
    // Edit other users
    $editPermissions = PermissionHelper::ACT_CREATE . " " . PermissionHelper::SUB_USER;
    Route::group(['middleware' => ["permission:{$editPermissions}"]], function () {
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('users/{id}/update', [UserController::class, 'update'])->name('users.update');
    });
    
    //Photography
    $permissionCanAccessPhotos = PermissionHelper::ACT_ACCESS . " " . PermissionHelper::SUB_PHOTOGRAPHY;
    Route::group(['middleware' => ["permission:{$permissionCanAccessPhotos}", CheckUserRestriction::class]], function () {
        Route::get('/photography', [PhotographyController::class, 'index'])->name('photography');
        Route::get('/photography/configure', [PhotographyController::class, 'showConfiguration'])->middleware(['role:Franchise'])->name('photography.configure-new');
        Route::get('/photography/portraits', [PhotographyController::class, 'showPortraits'])->name('photography.portraits');
        Route::get('/photography/groups', [PhotographyController::class, 'showGroups'])->name('photography.groups');
        Route::get('/photography/others', [PhotographyController::class, 'showOthers'])->name('photography.others');
        Route::post('/photography/request-download', [PhotographyController::class, 'requestDownloadDetails'])->name('photography.request-download');
        Route::post('/photography/request-download-nonce', [PhotographyController::class, 'execNonce'])->name('photography.request-download-nonce');
        Route::post('/photography/upload-image', [PhotographyController::class, 'uploadImage'])->name('photography.upload-image');
        Route::post('/photography/remove-image', [PhotographyController::class, 'removeImage'])->name('photography.remove-image');
    });
    // Proofing
    $permissionCanProof = PermissionHelper::ACT_ACCESS . " " . PermissionHelper::SUB_PROOFING;
    Route::group(['middleware' => ["permission:{$permissionCanProof}", CheckUserRestriction::class]], function () {
        //Dashboard
        Route::get('/proofing', [ProofHomeController::class, 'index'])->name('proofing');
        //Dashboard - open all seasons
        Route::get('/proofing/view-season', [ProofHomeController::class, 'viewSeason'])->name('dashboard.viewSeason');
        //Dashboard - pass specific season
        Route::post('/proofing/passSeason', [ProofHomeController::class, 'passSeason'])->name('dashboard.passSeason');
        //Dashboard - open specific seasons
        Route::get('/proofing/open-season/{selectedSeasonId}', [ProofHomeController::class, 'openSeason'])->name('dashboard.openSeason');
        //Dashboard - close season
        Route::get('/proofing/closeSeason', [ProofHomeController::class, 'closeSeason'])->name('dashboard.closeSeason');
        //Dashboard - Open Job
        Route::get('/proofing/openJob', [ProofHomeController::class, 'openJob'])->name('dashboard.openJob');
        //Dashboard - Archive Job
        Route::post('/proofing/jobs/archive', [ProofHomeController::class, 'archive'])->name('dashboard.archive');
        //Dashboard - Restore Job
        Route::post('/proofing/jobs/restore', [ProofHomeController::class, 'restore'])->name('dashboard.restore');
        //Dashboard - Show/Hide Archive Jobs
        Route::get('/proofing/jobs/toggle-archived', [ProofHomeController::class, 'toggleArchived'])->name('dashboard.toggleArchived');

        //Header - Close Job
        Route::get('/franchise/close-job', [ProofHomeController::class, 'closeJob'])->name('dashboard.closeJob');
        //Proofing - timeline insert
        Route::post('/franchise/config-job/proofing-timeline/submit', [ConfigureController::class, 'proofingTimelineInsert'])->name('config-job.proofingTimelineInsert');
        //Proofing - timeline email send
        Route::post('franchise/config-job/proofing-timeline/email-send', [ConfigureController::class, 'proofingTimelineEmailSend'])->name('config-job.proofingTimelineEmailSend');
        //Email-notifications enable
        Route::post('/franchise/config-job/email-notifications/enable', [ConfigureController::class, 'notificationEnable'])->name('config-job.notificationEnable');
        //Email-notifications matrix insert
        Route::post('/franchise/config-job/email-notifications/submit', [ConfigureController::class, 'notificationMatrixInsert'])->name('config-job.notificationMatrixInsert');
        //Folder-config update all
        Route::post('/franchise/config-job/folder-config/update/all', [ConfigureController::class, 'folderConfigAll'])->name('config-job.folderConfigAll');
        //Folder-Image Upload
        Route::post('/franchise/config-job/upload-file', [ImageController::class, 'groupImageUploadFile'])->name('groupImage.uploadFile');
        //Folder-Image Delete
        Route::post('/franchise/config-job/delete-file', [ImageController::class, 'groupImageDeleteFile'])->name('groupImage.deleteFile');
        //groupImage Show
        Route::get('/image/{filename}', [ImageController::class, 'showgroupImage'])->name('image.show');

        //Invitation
        Route::get('/franchise/invitations', [InvitationController::class, 'showInvitation'])->name('invitation.showInvitation');
        //Invitation - index
        Route::get('/proofing/invitations/index/{role}', [InvitationController::class, 'index'])->name('invitation.index');
        //Invitation - send
        Route::post('/proofing/invitations/send', [InvitationController::class, 'inviteSend'])->name('invitations.inviteSend');
        //Revoke - Folder Access
        Route::post('/proofing/folder/revoke/{userId}/{tsFolderId}/{tsJobId}', [InvitationController::class, 'revokeFolderUser'])->name('user.remove-from-folder');
        //Revoke - Job Access
        Route::post('/proofing/job/revoke/{userId}/{tsJobId}', [InvitationController::class, 'revokeJobUser'])->name('user.remove-from-job');
        //email-validation
        Route::post('/validate-email', [InvitationController::class, 'validateEmail'])->name('validate.email');
        //invitation-emailNotFound
        Route::get('/proofing/invitation/addUser', [InvitationController::class, 'emailNotFound'])->name('invitation.emailNotFound');

        //Change Proofing Status - Update folder Status
        Route::post('/franchise/folders/update-folder-status', [ReviewStatusController::class, 'updateFolderStatus'])->name('updateFolderStatus');
        //Change Proofing Status - Update job Status
        Route::post('/franchise/jobs/update-job-status', [ReviewStatusController::class, 'updateJobStatus'])->name('updateJobStatus');
        //Proof-my-people - Fetching all issues associated with folders and subjects for proofing
        Route::get('/franchise/proofing-description/{id}', [ProofController::class, 'ProofingDescription'])->name('proofing-description');
        //Proof-my-people - saving subject changes in proofing modal - second page
        Route::post('/franchise/proofing-change-log/subject-change/submit', [ProofController::class, 'insertSubjectProofingChangeLog'])->name('proofing-subject-change');
        //Proof-my-people - saving group changes in proofing - third page
        Route::post('/franchise/proofing-change-log/group-change/submit', [ProofController::class, 'insertGroupProofingChangeLog'])->name('proofing-group-change'); 
        //Proof-my-people - final submit in proofing - last page
        Route::post('/franchise/proofing-change-log/submit', [ProofController::class, 'submitProof'])->name('submit-proof');
        //Proof-my-people - subjects in grid
        Route::get('/subjects/grid', [ProofController::class, 'gridSubjects'])->name('subjects.grid');
        //Proof-my-people - Group Magnifying in proofing - third page
        Route::get('/franchise/zoom', [ImageController::class, 'zoom'])->name('zoom');    
        //Proof-my-people - Image Preview of subject in proofing - second page
        Route::get('network-image/{filename}/{jobKey}', [ImageController::class, 'serveImage'])->name('serve.image');

        //Bulk Upload - Upload
        Route::post('franchise/bulk-upload/groupImageUpload', [ImageController::class, 'groupImageUpload'])->name('groupImage.upload');
        //Bulk Upload - Delete Image
        Route::post('franchise/bulk-upload/groupImageDelete', [ImageController::class, 'groupImageDelete'])->name('groupImage.delete');
        //Bulk Upload - Folder Allocation
        Route::post('franchise/bulk-upload/groupImageSubmit', [ImageController::class, 'groupImageSubmit'])->name('groupImage.submit');

        //Fetch Constants
        Route::get('/constants', [ConstantsController::class, 'getConstants']);
    });
    //sync Job & associations

    //Proxy Sync Job
    Route::post('/proxy-sync-job', [ProofHomeController::class, 'proxySyncJob'])->name('proxy.syncJob');
    
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

    //Reports
        Route::get('/proofing/reports', [ReportController::class, 'index'])->name('reports');
    //Report - Run
        Route::get('/proofing/reports/{query}/{tsJobId?}/{tsFolderId?}', [ReportController::class, 'run'])->name('reports.run');
    //Report - Download
        Route::post('/proofing/reports/download', [ReportController::class, 'downloadReport'])->name('report.download');
    
});


Route::middleware(['auth', NoCacheHeaders::class, CheckJobSession::class])->group(function () {
        // Proofing
        $permissionCanProof = PermissionHelper::ACT_ACCESS . " " . PermissionHelper::SUB_PROOFING;
        Route::group(['middleware' => ["permission:{$permissionCanProof}", CheckUserRestriction::class]], function () {
            //Configure Job
            Route::get('/proofing/config-job/{hash}', [ConfigureController::class, 'index'])->name('config-job')
            // ->middleware(SetTimezone::class)
            ->middleware('signed');
            //TNJ Refresh
            Route::post('/franchise/config-job/{action}/{hash}', [ConfigureController::class, 'handleJobAction'])
                ->name('config-job-action')
                ->where('action', 'merge-duplicate-folders|merge-duplicate-subjects|update-subject-associations|update-people-images')->middleware('signed'); 
            //Delete Job - Used in Configure
            Route::post('/franchise/delete-job/{hash}', [ProofHomeController::class, 'deleteJob'])->name('dashboard.deleteJob')->middleware('signed');

            //Manage Photo Coordinators & Teachers
            Route::get('/proofing/staffs/{hash}', [InvitationController::class, 'manageStaffs'])->name('invitation.manageStaffs')->middleware('signed'); 
            
            //View Approved Changes
            Route::get('/proofing/subject-changes/approved/{hash}', [SubjectChangesController::class, 'approveChange'])->name('subject-change.approveChange')->middleware('signed'); 
            //View Unapproved Changes - Franchise
            Route::get('/proofing/subject-changes/franchise/awaitApproval/{hash}', [SubjectChangesController::class, 'awaitApproveChangeFranchise'])->name('subject-change-franchise.awaitApproveChange')->middleware('signed');
            //View Unapproved Changes - Photo Coordinator
            Route::get('/proofing/subject-changes/coordinator/awaitApproval/{hash}', [SubjectChangesController::class, 'awaitApproveChangeCoordinator'])->name('subject-change-coordinator.awaitApproveChange')->middleware('signed');
            //Submit Unapproved Changes - Photo Coordinator
            Route::post('/changes-action/{hash}', [SubjectChangesController::class, 'submitApproveChangeCoordinator'])->name('subject-change-coordinator.submitApproveChangeCoordinator')->middleware('signed');

            //Change Proofing Status
            Route::get('/proofing/folders/review-status/{hash}', [ReviewStatusController::class, 'changeStatus'])->name('folders.reviewStatus')->middleware('signed');

            //Proof-my-people - Folder and Subject Proofing - Wizard Page
            Route::get('/proofing/my-folders-validate/{hash}', [ProofController::class, 'MyFoldersValidate'])->name('my-folders-validate')->middleware('signed');
            
            //Proof-my-people - Folder listing associated with job
            Route::get('/proofing/my-folders-list/{hash}', [ProofController::class, 'MyFoldersList'])->name('my-folders-list')->middleware('signed');
            
            //Proof-my-people - saving folder changes in proofing - second page
            Route::post('/franchise/proofing-change-log/submit/{hash}', [ProofController::class, 'insertFolderProofingChangeLog'])->name('proofing-change-log')->middleware('signed');
            
            //Proof-my-people - view changes of subject in proofing modal - second page
            Route::get('/franchise/my-subject-change/{hash?}', [ProofController::class, 'viewChangeHtml'])->name('my-subject-change')->middleware('signed'); 
            
            //Bulk Upload
            Route::get('proofing/bulk-upload/{hash}/{step?}', [ImageController::class, 'bulkUploadImage'])->name('bulkUpload.image')->middleware('signed');
            
            //Invitation - single
            Route::get('/proofing/invitations/invite/single/{role}/{hash}', [InvitationController::class, 'inviteSingle'])->name('invitations.inviteSingle')->middleware('signed');
            //Invitation - multiple
            Route::get('/proofing/invitations/invite/multiple/{role}/{hash}', [InvitationController::class, 'inviteMulti'])->name('invitations.inviteMulti')->middleware('signed');
    });
});

require __DIR__.'/auth.php';
