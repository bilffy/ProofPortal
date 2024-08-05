<?php

namespace App\Helpers;

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
}