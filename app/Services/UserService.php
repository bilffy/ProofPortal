<?php

namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Models\User;
use App\Jobs\SendUserInviteJob;
use App\Jobs\SendOTPJob;
use App\Models\UserOtp;

class UserService
{
    /**
     * Send an invitation email to the user.
     *
     * @param User $user
     * @return void
     */
    public function sendInvite(User $user): void
    {
        // Dispatch the email sending an invitation job to the queue
        SendUserInviteJob::dispatch($user);
        // Log SEND_INVITE activity
        ActivityLogHelper::log(LogConstants::SEND_INVITE, ['user_id' => $user->id]);
    }
    
    public function sendOtp(User $user): void
    {
        // Dispatch the email sending an otp job to the queue
        SendOTPJob::dispatch($user);
    }
    
    public function getRecentOtp(User $user)
    {
        return UserOtp::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}