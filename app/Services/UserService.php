<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\SendUserInviteJob;

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
        // Dispatch the email sending job to the queue
        SendUserInviteJob::dispatch($user);
    }
}