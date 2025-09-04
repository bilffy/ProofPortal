<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Models\User;
use Auth;
use App\Helpers\SchoolContextHelper;
use Vinkla\Hashids\Facades\Hashids;

class ImpersonateController extends Controller
{
    public function store(string $id)
    {
        // $id = Hashids::decodeHex($id);
        
        // Retrieve the user by ID
        /** @var User $user */
        $user = User::findOrFail($id);

        if (!session()->has('root_user_id')) {
            session()->put('root_user_id', Auth::id());
        }
        $rootUserId = Auth::id();
        Auth::user()->impersonate($user);
        // Log IMPERSONATE_USER activity
        ActivityLogHelper::log(LogConstants::IMPERSONATE_USER, ['impersonated_user' => $user->id], $rootUserId);

        return redirect()->route('dashboard')->with('success', 'You are logged in as ' . $user->email);
    }

    public function leave()
    {
        if (SchoolContextHelper::isSchoolContext()) {
            SchoolContextHelper::removeSchoolContext();
        }
        
        $rootUserId = session()->pull('root_user_id');
        $impersonatedId = Auth::user()->getAuthIdentifier();
        Auth::user()->leaveImpersonation();
        
        if ($rootUserId) {
            Auth::loginUsingId($rootUserId);
        }
        // Log EXIT_IMPERSONATE_USER activity
        ActivityLogHelper::log(LogConstants::EXIT_IMPERSONATE_USER, ['impersonated_user' => $impersonatedId]);

        return redirect()->route('dashboard');
    }
}
