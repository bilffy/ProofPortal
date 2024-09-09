<?php

namespace App\Helpers;

use App\Models\User;

class UserStatusHelper
{
    public static function getBadge(string $status): string
    {
        $userStatuses = [
            User::STATUS_ACTIVE => 'gray',
            User::STATUS_INVITED => 'blue',
            User::STATUS_DISABLED => 'red',
            User::STATUS_NEW => 'green',
        ];

        return $userStatuses[$status] ?? 'green';
    }
}