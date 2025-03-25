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
     * @param int $senderId
     * @return void
     */
    public function sendInvite(User $user, int $senderId): void
    {
        // Dispatch the email sending an invitation job to the queue
        SendUserInviteJob::dispatch($user, $senderId);
        // Log SEND_INVITE activity
        ActivityLogHelper::log(LogConstants::SEND_INVITE, ['invited_user' => $user->id]);
    }
    
    /**
     * Send an OTP to the user.
     *
     * @param User $user
     * @param UserOtp|null $userOtp
     * @return void
     */
    public function sendOtp(User $user, UserOtp $userOtp = null): void
    {
        // Dispatch the email sending an otp job to the queue
        SendOTPJob::dispatch($user, $userOtp);
    }
    
    public function getRecentOtp(User $user)
    {
        return UserOtp::where('user_id', $user->id)
            ->latest('created_at')
            ->first();
    }
}