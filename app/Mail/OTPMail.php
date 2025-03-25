<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OTPMail extends Mailable
{
    use Queueable, SerializesModels;

    private User $user;
    private int $otp;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Get OTP expiration time from .env
        $expiration = config('app.otp.expiration_minutes', 60);
        
        return $this->markdown('emails.otp')
            ->subject('Your MSP Photography Security Code')
            ->with([
                'user' => $this->user,
                'otp' => $this->otp,
                'expiration' => $expiration,
                'franchise' => $this->user->getOrganization(),
            ]);
    }
}