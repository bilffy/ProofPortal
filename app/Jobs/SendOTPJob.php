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
use Illuminate\Support\Facades\Config;

class SendOTPJob implements ShouldQueue
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
        // Generate the otp
        $otp = OTPHelper::generateOtp();
        
        // Send the invite email
        Mail::to($this->user->email)->send(new OTPMail($this->user, $otp));

        // Get OTP expiration time from .env
        $expirationMinutes = Config::get('otp.expiration_minutes', 60);
    
        // Save the otp to the database
        UserOtp::create([
            'user_id' => $this->user->id,
            'otp' => $otp,
            'expire_on' => now()->addMinutes($expirationMinutes),
        ]);
    }
}