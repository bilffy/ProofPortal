<?php

namespace App\Helpers;

use Crypt;

class EncryptionHelper
{
    public static function simpleEncrypt(string $data): string
    {
        return base64_encode(base64_encode($data));
        // return Crypt::encrypt($data);
    }

    public static function simpleDecrypt(string $data): string
    {
        return base64_decode(base64_decode($data));
        // return Crypt::decryptString($data);
    }
}
