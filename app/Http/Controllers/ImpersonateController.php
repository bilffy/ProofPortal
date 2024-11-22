<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Route;
use App\Helpers\SchoolContextHelper;

class ImpersonateController extends Controller
{
    public function store(int $id)
    {
        // Retrieve the user by ID
        /** @var User $user */
        $user = User::findOrFail($id);

        if (!session()->has('root_user_id')) {
            session()->put('root_user_id', Auth::id());
        }
        
        Auth::user()->impersonate($user);
        
        return redirect()->route('dashboard')->with('success', 'You are logged in as ' . $user->email);
    }

    public function leave()
    {
        $rootUserId = session()->pull('root_user_id');
        
        Auth::user()->leaveImpersonation();

        if ($rootUserId) {
            Auth::loginUsingId($rootUserId);
        }

        return redirect()->route('dashboard');
    }
}
