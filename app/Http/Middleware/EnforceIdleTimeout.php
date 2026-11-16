<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Driver-independent idle timeout. The session driver's own expiry is refreshed by every
 * request (redis/file drivers also by the background /api/ping), so an open tab could keep
 * a login alive for days. This stores the time of the last real (non-ping) request in the
 * session and logs the user out when it is older than session.lifetime.
 */
class EnforceIdleTimeout
{
    public const KEY = 'last_real_activity';

    public static function isIdleExpired(Request $request): bool
    {
        $last = $request->session()->get(self::KEY);

        return $last !== null && (time() - (int) $last) > (int) config('session.lifetime') * 60;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! Auth::check()) {
            return $next($request);
        }

        if (self::isIdleExpired($request)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Session expired'], 401)
                : redirect()->route('login');
        }

        // Background polling must not count as activity.
        if (! $request->is('api/ping')) {
            $request->session()->put(self::KEY, time());
        }

        return $next($request);
    }
}
