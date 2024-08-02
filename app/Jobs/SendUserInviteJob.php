<?php

namespace App\Jobs;

use App\Mail\UserInviteMail;
use App\Models\User;
use App\Models\UserInviteToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendUserInviteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Generate the invite link
        $token = \Str::random(40);
        $inviteLink = url("/invite/{$token}");

        // Send the invite email
        Mail::to($this->user->email)->send(new UserInviteMail($this->user, $inviteLink));

        // Save the token to the database
        UserInviteToken::create([
            'user_id' => $this->user->id,
            'token' => $token,
        ]);
    }
}