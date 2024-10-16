<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Http\Resources\UserResource;
use Auth;
use Route;

class DashboardController extends Controller
{
    public function index()
    {   
        $user = Auth::user();
        
        // Redirect to the school list page if the user is an admin
        if ($user->hasRole( RoleHelper::ROLE_ADMIN )) {
            return redirect()->route('school.list');
        }
        
        return view('dashboard', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'user' => new UserResource($user)
        ]);
    }
}
