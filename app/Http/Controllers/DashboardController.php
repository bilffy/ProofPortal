<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use App\Helpers\RoleHelper;
use App\Helpers\SchoolContextHelper;
use App\Http\Resources\UserResource;
use App\Models\User;
use Auth;
use Route;

class DashboardController extends Controller
{   
    public function index()
    {   
        /** @var User $user */
        $user = Auth::user();
        
        // Redirect to the school list page if the user is an RC or franchise
        if ($user->hasAnyRole( [RoleHelper::ROLE_ADMIN, RoleHelper::ROLE_FRANCHISE] )) {
            
            if (SchoolContextHelper::isSchoolContext()) {
                SchoolContextHelper::removeSchoolContext();
            }
            
            return redirect()->route('school.list');
        }
        
        return view('dashboard', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'user' => new UserResource($user)
        ]);
    }
}
