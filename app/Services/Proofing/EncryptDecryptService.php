<?php

namespace App\Services\Proofing;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptDecryptService
{
    /**
     * Decrypts a string safely by checking for null/empty values first.
     */
    public function decryptStringMethod($encrypted)
    {
        // 1. Immediate check: If it's null or empty, don't attempt decryption
        if (is_null($encrypted) || $encrypted === '') {
            return null; 
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException $e) {
            // Log the error for debugging
            \Log::error('Decryption failed for value: ' . $encrypted);
            throw new \Exception('Decryption failed: ' . $e->getMessage());
        }
    }

    /**
     * You should also add a null-safe encryption method 
     * as this is usually where the 'openssl_encrypt' warning starts.
     */
    public function encryptStringMethod($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        return Crypt::encryptString($value);
    }
}