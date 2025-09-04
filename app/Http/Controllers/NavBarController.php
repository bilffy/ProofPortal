<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UiSetting;
use Auth;

class NavBarController extends Controller
{
    public function toggleCollapse(Request $request)
    {   
        $user = Auth::user();
        
        $collapsed = $request->input('collapsed');
        UiSetting::setNavCollapsed($collapsed === 'true'?? false, $user);
        return response()->json(['collapsed' => $collapsed]);
    }
}