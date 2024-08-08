<?php

namespace App\Notifications;

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
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $expiration = config('auth.passwords.users.expire');
        $resetUrl = url(config('app.url').route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()], false));

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->markdown('emails.reset_password', [
                'resetUrl' => $resetUrl,
                'expiration' => $expiration,
            ]);
    }
}