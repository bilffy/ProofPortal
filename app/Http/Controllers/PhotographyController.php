<?php

namespace App\Http\Controllers;

use App\Helpers\SchoolContextHelper;
use Auth;
use App\Http\Resources\UserResource;

class PhotographyController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->isFranchiseLevel() && SchoolContextHelper::isSchoolContext()) {
            return redirect()->route('photography.configure');
        } else {
            return redirect()->route('photography.portraits');
        }
    }

    public function configure()
    {
        return view('photography', ['user' => new UserResource(Auth::user()), 'currentTab' => 'configure']);
    }

    public function showPortraits()
    {
        return view('photography', ['user' => new UserResource(Auth::user()), 'currentTab' => 'portraits']);
    }

    public function showGroups()
    {
        return view('photography', ['user' => new UserResource(Auth::user()), 'currentTab' => 'groups']);
    }

    public function showOthers()
    {
        return view('photography', ['user' => new UserResource(Auth::user()), 'currentTab' => 'others']);
    }
}
