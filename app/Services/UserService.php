<?php

namespace App\Services;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Models\User;
use App\Models\School;
use App\Jobs\SendUserInviteJob;
use App\Jobs\SendOTPJob;
use App\Models\UserOtp;

class UserService
{
    /**
     * Send an invitation email to the user.
     *
     * @param User $user
     * @param int $senderId
     * @return void
     */
    public function sendInvite(User $user, int $senderId): void
    {
        // Dispatch the email sending an invitation job to the queue
        SendUserInviteJob::dispatch($user, $senderId);
        // Log SEND_INVITE activity
        ActivityLogHelper::log(LogConstants::SEND_INVITE, ['invited_user' => $user->id]);
    }
    
    /**
     * Send an OTP to the user.
     *
     * @param User $user
     * @param UserOtp|null $userOtp
     * @return void
     */
    public function sendOtp(User $user, UserOtp $userOtp = null): void
    {
        // Dispatch the email sending an otp job to the queue
        SendOTPJob::dispatch($user, $userOtp);
    }
    
    public function getRecentOtp(User $user)
    {
        return UserOtp::where('user_id', $user->id)
            ->latest('created_at')
            ->first();
    }

    /**
     * Check if the user can access images of the given school.
     * @param User $user
     * @param School $school
     * @return bool
     */
    public static function isCanAccessImage(User $user, School $school): bool
    {
        if ($user->isFranchiseLevel() && $school) {
            // Make sure user belongs to the same school's franchise.
            if (!$school->franchises()->where('franchise_id', $user->getFranchise()->id)->exists()){
                return false;
            }
        } else {
            // Make sure user has the same school being access with
            if ($user->getSchool()->id !== $school->id) {
                return false;
            }
        }

        return true;
    }
}