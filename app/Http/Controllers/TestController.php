<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    public function index()
    {
      // $user = User::where('id', 1)->first();
      return Inertia::render('App', [
        'test_val' => 'Prop value',
        // 'user' => $user
      ]);
    }
}
