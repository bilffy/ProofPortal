<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    // public function ping(Request $request)
    // {
    //     $user = auth('sanctum')->user();
    //     $sessionActivity = DB::table('sessions')
    //         ->where('user_id', $user->id)
    //         ->select('last_activity')
    //         ->orderByDesc('last_activity')
    //         ->get()->first();
    //     $maxIdleTime = config('session.lifetime') * 60;
        
    //     try {
    //         $isAlive = time() - $sessionActivity->last_activity < $maxIdleTime;
    //     } catch (\Exception $e) {
    //         $isAlive = false;
    //     }

    //     $data = [
    //         'is_alive' => $isAlive
    //     ];
        
    //     if ($isAlive) {
    //         return response()->json(array_merge(
    //             $data,
    //             ['status' => 200, 'message' => 'Session is alive']
    //         ));
    //     }

    //     return response()->json(array_merge(
    //         $data,
    //         ['status' => 401, 'message' => 'Session expired']
    //     ), 401);
    // }

    public function ping(Request $request)
    {
        // Check if the current request session is valid
        $isAlive = false;

        if ($request->session()->isValid()) {
            $lastActivity = $request->session()->get('last_activity', time());
            $maxIdleTime = config('session.lifetime') * 60;
            
            $isAlive = (time() - $lastActivity) < $maxIdleTime;
        }

        $data = [
            'is_alive' => $isAlive
        ];

        return response()->json($data);
    }
}
