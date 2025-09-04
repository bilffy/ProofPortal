<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class NoCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip caching headers for redirects
        if ($response instanceof RedirectResponse) {
            return $response;
        }

        // Add headers safely for JSON, HTML, and file responses
        $headers = [
            'Cache-Control' => 'no-store, no-transform, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ];

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
