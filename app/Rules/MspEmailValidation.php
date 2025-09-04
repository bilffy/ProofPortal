<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class MspEmailValidation implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ('email') {
            // WIP: Transfer to a database table
            $allowedDomains = [
                "msp.com",
                "chromedia.com",
                "gmail.com", // For testing only
                "email.com", // For testing only
                "test.com", // For testing only
                "example.com", // For testing only
            ];

            try {
                list($name, $domain) = explode('@', $value);
                
                if (!in_array($domain, $allowedDomains)) {
                    $fail("Email domain is not supported. Please contact your Administrator.");
                }
            } catch (Throwable $e) {
                $fail("Invalid Format");
            }
        }
    }
}
