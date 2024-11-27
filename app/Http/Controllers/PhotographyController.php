<?php

namespace App\Http\Controllers;

use Auth;
use App\Http\Resources\UserResource;

class PhotographyController extends Controller
{
    public function index()
    {
        return view('photography', ['user' => new UserResource(Auth::user())]);
    }
}
