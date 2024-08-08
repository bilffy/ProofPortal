<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;

class OTPHelper
{
    /**
     * Generate a random 6-digit OTP.
     *
     * @return int
     */
    public static function generateOtp(): int
    {
        return random_int(100000, 999999);
    }

    /**
     * Encrypt the given OTP using the security pin.
     *
     * @param $otp
     * @return string
     */
    public static function encryptOtp($otp): string
    {
        $securityPin = config('app.otp.secret_pin');
        return Crypt::encryptString($otp . $securityPin);
    }

    /**
     * Decrypt the given encrypted OTP using the security pin.
     *
     * @param string $encryptedOtp
     * @throw \Illuminate\Contracts\Encryption\DecryptException
     * @return int
     */
    public static function decryptOtp(string $encryptedOtp): int
    {
        $securityPin = config('app.otp.secret_pin');
        $decrypted = Crypt::decryptString($encryptedOtp);
        return (int)str_replace($securityPin, '', $decrypted);
    }
}