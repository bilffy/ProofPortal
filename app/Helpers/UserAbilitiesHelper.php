<?php

namespace App\Helpers;

use App\Models\User;

class UserAbilitiesHelper
{
    public static function canInviteUser($inviter, $invitee)
    {
        $isValidStatus = $invitee->status == User::STATUS_NEW || $invitee->status == User::STATUS_INVITED;
        $isInvitable = in_array($invitee->getRole(), $inviter->getInvitableRoles());
        $isNotUser = $inviter->id !== $invitee->id;
        return $isInvitable && $isValidStatus && $isNotUser;
    }
}