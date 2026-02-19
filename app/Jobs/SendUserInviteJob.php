<?php

namespace App\Jobs;

use App\Mail\UserInviteMail;
use App\Models\User;
use App\Models\Status;
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
    protected int $senderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, int $senderId)
    {
        $this->user = $user;
        $this->senderId = $senderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $token = Password::createToken($this->user);

        $setupUrl = route('account.setup.create', [
            'token' => $token,
            'email' => $this->user->getHashedIdAttribute()
        ]);
        
        
        // Set the user status to invited
        $this->user->status = User::STATUS_INVITED;

        $status = Status::where('status_external_name', 'invited')->first();
        $this->user->active_status_id = $status->id;
        
        $this->user->save();
        
        // Send the invite email
        Mail::to($this->user->email)->send(new UserInviteMail($this->user, $this->senderId, $setupUrl));
    }
}