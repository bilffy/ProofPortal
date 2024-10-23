<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UiSetting;

class NavBarController extends Controller
{
    public function toggleCollapse(Request $request)
    {
        $collapsed = $request->input('collapsed');
        UiSetting::setNavCollapsed($collapsed === 'true'?? false);
        return response()->json(['collapsed' => $collapsed]);
    }
}