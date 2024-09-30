<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Route;

class TestController extends Controller
{
    public function index()
    {
        return view('test', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'user' => new UserResource(Auth::user())
        ]);
    }
    public function test2()
    {
        return view('test2', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'user' => new UserResource(Auth::user())
        ]);
    }
}
