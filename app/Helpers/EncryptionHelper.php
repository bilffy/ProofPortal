<?php

namespace App\Helpers;

class EncryptionHelper
{
    public static function simpleEncrypt(string $data): string
    {
        if ($data === null) {
            return '';
        }
        return bin2hex($data);
    }

    public static function simpleDecrypt(string $data = null): string|null
    {   
        if ($data === null) {
            return null;
        }

        return hex2bin($data);
    }
}
