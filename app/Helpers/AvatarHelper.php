<?php

namespace App\Helpers;

use App\Models\User;

class AvatarHelper
{
    public static function getInitials(User $user): string
    {
        $initials = '';
        $firstName = $user->firstname;
        $lastName = $user->lastname;

        if (!empty($firstName)) {
            $initials .= strtoupper($firstName[0]);
        }

        if (!empty($lastName)) {
            $initials .= strtoupper($lastName[0]);
        }

        return $initials;
    }
}