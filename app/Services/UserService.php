<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\SendUserInviteJob;
use App\Jobs\SendOTPJob;

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
    }
    
    public function sendOtp(User $user): void
    {
        // Dispatch the email sending an otp job to the queue
        SendOTPJob::dispatch($user);
    }
}