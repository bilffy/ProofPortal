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
        
        Auth::user()->impersonate($user);

        return redirect()->route('dashboard')->with('success', 'You are logged in as ' . $user->email);
    }

    public function leave()
    {
        Auth::user()->leaveImpersonation();

        return redirect()->route('dashboard');
    }
}
