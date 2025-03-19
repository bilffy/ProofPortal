<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function ping(Request $request)
    {
        $user = auth('sanctum')->user();
        $sessionActivity = DB::table('sessions')
            ->where('user_id', $user->id)
            ->select('last_activity')
            ->orderByDesc('last_activity')
            ->get()->first();
        $maxIdleTime = config('session.lifetime') * 60;
        
        $isAlive = time() - $sessionActivity->last_activity < $maxIdleTime;

        $data = [
            'is_alive' => $isAlive
        ];
        
        if ($isAlive) {
            return response()->json(array_merge(
                $data,
                ['status' => 200, 'message' => 'Session is alive']
            ));
        }

        return response()->json(array_merge(
            $data,
            ['status' => 401, 'message' => 'Session expired']
        ), 401);
    }
}
