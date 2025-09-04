<?php

namespace App\Http\Controllers;

use Auth;
use App\Http\Resources\UserResource;

class ProofingController extends Controller
{
    public function index()
    {
        return view('proofing', ['user' => new UserResource(Auth::user())]);
    }
}
