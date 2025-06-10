<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Auth;

class SettingsController extends Controller
{
    public function main(Request $request)
    {   
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard');
        }
        
        return view('settings', [
            'user' => new UserResource($user),
            
        ]);
    }
}