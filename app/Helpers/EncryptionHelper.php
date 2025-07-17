<?php

namespace App\Helpers;

use Nullix\JsAesPhp\JsAesPhp;

class EncryptionHelper
{
    public static function simpleEncrypt(string $data, $nonce): string
    {
        try {
            $encrypted = JsAesPhp::encrypt($data, $nonce);
        } catch (\Exception $e) {
            return '';
        }

        return $encrypted;
    }

    public static function simpleDecrypt(string $data = null, $nonce): string|null
    {
        try {
            $decrypted = JsAesPhp::decrypt($data, $nonce);
        } catch (\Exception $e) {
            return null;
        }

        return $decrypted;
    }
}
