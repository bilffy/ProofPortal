<?php

namespace App\Jobs;

use App\Helpers\OTPHelper;
use App\Mail\OTPMail;
use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOTPJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected UserOtp|null $userOtp = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, UserOtp $userOtp = null)
    {
        $this->user = $user;
        $this->userOtp = $userOtp;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Generate the otp
        $otp = OTPHelper::generateOtp();
        
        // Send the otp to the user
        Mail::to($this->user->email)->send(new OTPMail($this->user, $otp));

        // Get OTP expiration time from .env
        $expirationMinutes = (int) config('app.otp.expiration_minutes', 60);
        
        if ($this->userOtp instanceof UserOtp) {
            // Update the existing otp
            $this->userOtp->update([
                'otp' => OTPHelper::encryptOtp($otp),
                'last_resend_at' => now(),
                'expire_on' => now()->addMinutes($expirationMinutes),
                'otp_attempts' => 0, // Reset the otp attempts count to 0
            ]);
        } else {
            // Save the otp to the database
            UserOtp::create([
                'user_id' => $this->user->id,
                'otp' => OTPHelper::encryptOtp($otp),
                'expire_on' => now()->addMinutes($expirationMinutes),
            ]);
        }
    }
}