<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps /api/ping from refreshing sessions.last_activity.
 * Without this, background polling permanently extends idle lifetime
 * (especially in browsers like Firefox that keep timers running).
 */
class DoNotExtendSessionLifetime
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('session.driver') === 'database' && $request->hasSession()) {
            $sessionId = $request->session()->getId();
            $previousLastActivity = DB::table(config('session.table', 'sessions'))
                ->where('id', $sessionId)
                ->value('last_activity');

            if ($previousLastActivity !== null) {
                // Run after StartSession::terminate() writes the refreshed timestamp.
                app()->terminating(function () use ($sessionId, $previousLastActivity) {
                    DB::table(config('session.table', 'sessions'))
                        ->where('id', $sessionId)
                        ->update(['last_activity' => $previousLastActivity]);
                });
            }
        }

        return $next($request);
    }
}
