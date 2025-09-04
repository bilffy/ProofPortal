<?php

namespace App\Mail;

use App\Models\Franchise;
use App\Models\User;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    private User $user;
    private int $senderId;
    private string $inviteLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $senderId, $inviteLink)
    {
        $this->user = $user;
        $this->senderId = $senderId;
        $this->inviteLink = $inviteLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {   
        $sender = User::find($this->senderId);
        $uOrgName = $this->user->getSchoolOrFranchise(true);
        $sOrgName = $sender->getSchoolOrFranchise(true);
        return $this->markdown('emails.user_invite')
            ->subject('MSP account setup')
            ->with([
                'user' => $this->user,
                'sender' => $sender,
                'userOrgName' => $this->user->isSchoolLevel() ? $uOrgName : "MSP " . $uOrgName,
                'senderOrgName' => $sender->isSchoolLevel() ? $sOrgName : "MSP " . $sOrgName,
                'franchise' => $sender->getOrganization(),
                'inviteLink' => $this->inviteLink,
            ]);
    }
}