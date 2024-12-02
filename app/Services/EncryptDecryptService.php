<?php

namespace App\Services;
use Illuminate\Support\Facades\Crypt;

class EncryptDecryptService
{
    public function decryptStringMethod($encrypted){
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Log or handle the decryption failure
            throw new \Exception('Decryption failed: ' . $e->getMessage());
        }
    }


}
