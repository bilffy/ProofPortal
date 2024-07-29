<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\UserInviteMail;
use App\Models\User;
use App\Models\UserInviteToken;

class UserService
{
    /**
     * Send an invite email to the user.
     *
     * @param string $email
     * @param User $user
     * @return void
     */
    public function sendInvite(string $email, User $user): void
    {
        // Generate the invite link
        $token = \Str::random(40); 
        $inviteLink = url("/invite/{$token}");
        
        // Send the invite email
        Mail::to($email)->send(new UserInviteMail($user, $inviteLink));

        // Save the token to the database
        UserInviteToken::create([
            'user_id' => $user->id,
            'token' => $token,
        ]);
    }
}