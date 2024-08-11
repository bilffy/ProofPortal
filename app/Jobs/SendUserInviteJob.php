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
use Illuminate\Support\Facades\Password;

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
        $token = Password::createToken($this->user);

        $setupUrl = url(config('app.url').route('account.setup.create', 
                [
                    'token' => $token, 
                    'email' => $this->user->email
                ], 
                false
            )
        );
        
        // Set the user status to invited
        $this->user->status = User::STATUS_INVITED;
        $this->user->save();
        
        // Send the invite email
        Mail::to($this->user->email)->send(new UserInviteMail($this->user, $setupUrl));
    }
}