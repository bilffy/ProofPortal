<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if (!$request->hasSession() || !Auth::check()) {
            return response()->json(['is_alive' => false]);
        }

        $isAlive = $this->sessionExists($request->session()->getId());

        return response()->json([
            'is_alive' => $isAlive,
        ]);
    }

    private function sessionExists(string $sessionId): bool
    {
        return match (config('session.driver')) {
            'redis' => $this->sessionExistsInRedis($sessionId),
            'database' => $this->sessionExistsInDatabase($sessionId),
            'file' => is_file(config('session.files') . '/' . $sessionId),
            default => false,
        };
    }

    private function sessionExistsInRedis(string $sessionId): bool
    {
        try {
            $sessionKey = Str::slug(config('app.name', 'laravel'), '_') . '_session:' . $sessionId;

            return (bool) Redis::connection('default')->exists($sessionKey);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function sessionExistsInDatabase(string $sessionId): bool
    {
        $maxIdleTime = config('session.lifetime') * 60;

        return DB::table(config('session.table'))
            ->where('id', $sessionId)
            ->where('last_activity', '>', time() - $maxIdleTime)
            ->exists();
    }
}
