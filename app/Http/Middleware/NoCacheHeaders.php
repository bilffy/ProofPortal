<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
// use Auth;

class NoCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   
        // $user = Auth::user();

        // // Check if user is logged in and disabled
        // if ($user && $user->disabled) {
        //     Auth::logout(); // log out user
        //     $request->session()->invalidate(); // clear session
        //     $request->session()->regenerateToken(); // regenerate CSRF token

        //     return redirect()->route('login')
        //         ->withErrors(['email' => 'Your account has been disabled. Please contact admin.']);
        // }

        $response = $next($request);
        // If no header method, return response as is
        if (!method_exists($response, 'header')) {
            return $response;
        }
        return $response
            ->header('Cache-Control', 'no-store, no-transform, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
