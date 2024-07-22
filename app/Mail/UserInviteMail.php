<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $inviteLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $inviteLink)
    {
        $this->user = $user;
        $this->inviteLink = $inviteLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.user_invite')
            ->with([
                'user' => $this->user,
                'inviteLink' => $this->inviteLink,
            ]);
    }
}