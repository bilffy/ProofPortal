<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Auth;
use Route;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'user' => new UserResource(Auth::user())
        ]);
    }
}
