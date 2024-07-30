<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Route;

class TestController extends Controller
{
    public function index()
    {
      return Inertia::render('App', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'user' => new UserResource(Auth::user())
      ]);
    }
}
