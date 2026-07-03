<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

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
        $isAlive = false;
        
        $cookieName = config('session.cookie');
        $sessionId = $request->cookie($cookieName);

        if ($sessionId) {
            try {
                $decryptedId = decrypt($sessionId, false);
                
                if (str_contains($decryptedId, '|')) {
                    $decryptedId = explode('|', $decryptedId)[1];
                }
                
                $sessionKey = Str::slug(config('app.name', 'laravel'), '_') . '_session:' . $decryptedId;

                // Directly checks Redis DB 0 via the 'default' connection config
                if (Redis::connection('default')->exists($sessionKey)) {
                    $isAlive = true;
                }
            } catch (\Exception $e) {
                $isAlive = false;
            }
        }

        return response()->json([
            'is_alive' => $isAlive
        ]);
    }
}
