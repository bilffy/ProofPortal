<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Class ResetPassword
 * 
 * This class is used to customize the password reset email notification.
 * @package App\Notifications
 */
class ResetPassword extends Notification
{
    private User $user;
    public $token;

    public function __construct($token, User $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $expiration = config('auth.passwords.users.expire');
        $resetUrl = url(config('app.url').route('password.reset', 
                [
                    'token' => $this->token, 
                    //'email' => $notifiable->getEmailForPasswordReset()
                    'email' => $this->user->getHashedIdAttribute()
                ], 
                false));
        
        $fullName = ucfirst($this->user->firstname) .' '. ucfirst($this->user->lastname);
        
        return (new MailMessage)
            ->subject('Reset your MSP School Portal password')
            ->markdown('emails.reset_password', [
                'firstname' => $this->user->firstname,
                'resetUrl' => $resetUrl,
                'expiration' => $expiration,
                'franchise' => $this->user->getOrganization(),
            ]);
    }
}