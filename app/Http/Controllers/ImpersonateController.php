<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Models\User;
use Auth;
use App\Helpers\SchoolContextHelper;
use App\Helpers\PermissionHelper;

class ImpersonateController extends Controller
{
    private const ROOT_USER_SESSION_KEY = 'root_user_id';

    public function store(string $id)
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        // Always remember the true logged-in user (not an intermediate impersonation).
        if (!session()->has(self::ROOT_USER_SESSION_KEY)) {
            session()->put(self::ROOT_USER_SESSION_KEY, Auth::id());
        }
        $rootUserId = (int) session(self::ROOT_USER_SESSION_KEY);

        // Never nest: return to the root user before starting a new impersonation.
        $this->returnToRootUser($rootUserId);

        if (!PermissionHelper::canImpersonate($id)) {
            abort(403, 'You are not authorized to impersonate this user.');
        }

        Auth::user()->impersonate($user);

        ActivityLogHelper::log(LogConstants::IMPERSONATE_USER, ['impersonated_user' => $user->id], $rootUserId);

        $this->clearProofingSessionContext();

        return redirect()->route('dashboard')->with('success', 'You are logged in as ' . $user->email);
    }

    public function leave()
    {
        if (SchoolContextHelper::isSchoolContext()) {
            SchoolContextHelper::removeSchoolContext();
        }

        $this->clearProofingSessionContext();

        $impersonatedId = Auth::user()?->getAuthIdentifier();
        $rootUserId = session()->pull(self::ROOT_USER_SESSION_KEY);

        $this->returnToRootUser($rootUserId ? (int) $rootUserId : null);

        ActivityLogHelper::log(LogConstants::EXIT_IMPERSONATE_USER, ['impersonated_user' => $impersonatedId]);

        return redirect()->route('dashboard');
    }

    /**
     * Leave any nested impersonation stack and log in as the original user.
     */
    private function returnToRootUser(?int $rootUserId): void
    {
        // Peel every impersonation layer (Admin → A → B should fully unwind).
        $safety = 0;
        while (Auth::check() && Auth::user()->isImpersonated() && $safety < 10) {
            Auth::user()->leaveImpersonation();
            $safety++;
        }

        if ($rootUserId && Auth::id() != $rootUserId) {
            Auth::loginUsingId($rootUserId);
        }
    }

    private function clearProofingSessionContext(): void
    {
        session()->forget([
            'job-season-flag',
            'selectedJob',
            'selectedSeason',
            'openJob',
            'selectedSeasonDashboard',
            'openSeason',
            'approvedSubjectChangesCount',
            'awaitApprovalSubjectChangesCount',
        ]);
    }
}
