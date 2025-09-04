<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConstantsController extends Controller
{
    public function getConstants()
    {
        return response()->json(config('constants'));
    }
}
