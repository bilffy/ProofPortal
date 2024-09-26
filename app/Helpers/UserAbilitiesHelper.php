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

    // TODO: Update helpers based on future role-permission implementation
    public static function canUseAdminTools($userRole)
    {
        $restrictedRoles = [
            RoleHelper::ROLE_TEACHER,
        ];
        return !in_array($userRole, $restrictedRoles);
    }
}