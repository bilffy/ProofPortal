<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep generated URLs (login redirects, etc.) on the current request host.
 * Prevents local http://127.0.0.1:8000 AJAX from being redirected to production APP_URL.
 */
class ForceRequestRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $root = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');
        if ($root !== '') {
            URL::forceRootUrl($root);
        }

        // Do not force https when developing over plain http on localhost.
        $host = $request->getHost();
        if (in_array($host, ['127.0.0.1', 'localhost'], true) && !$request->isSecure()) {
            URL::forceScheme('http');
        }

        return $next($request);
    }
}
