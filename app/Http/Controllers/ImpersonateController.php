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

        // Never nest: unwind an active impersonation before starting a new one.
        if (Auth::check() && Auth::user()->isImpersonated()) {
            $this->returnToRootUser($rootUserId);
        }

        if (!PermissionHelper::canImpersonate($id)) {
            abort(403, 'You are not authorized to impersonate this user.');
        }

        Auth::user()->impersonate($user);

        $this->syncWebPasswordHashInSession();

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

        $this->syncWebPasswordHashInSession();

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
            $rootUser = User::find($rootUserId);
            if ($rootUser) {
                $guard = Auth::guard('web');
                if ($guard instanceof \App\Auth\ImpersonateSessionGuard) {
                    $guard->quietLogin($rootUser);
                } else {
                    Auth::loginUsingId($rootUserId);
                }
            }
        }
    }

    /**
     * Keep Sanctum's session fingerprint aligned with the active web user.
     * Without this, stateful API requests can flush the session after impersonation.
     */
    private function syncWebPasswordHashInSession(): void
    {
        if (!request()->hasSession()) {
            return;
        }

        $guard = Auth::guard('web');
        $user = $guard->user();

        if (!$user) {
            return;
        }

        request()->session()->put([
            'password_hash_web' => method_exists($guard, 'hashPasswordForCookie')
                ? $guard->hashPasswordForCookie($user->getAuthPassword())
                : $user->getAuthPassword(),
        ]);
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
