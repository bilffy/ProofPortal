<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add this specifically for UAT/Live environments
        $middleware->trustProxies(at: '*'); 
        $middleware->web(prepend: [
            \App\Http\Middleware\ForceRequestRootUrl::class,
        ]);
        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'proofing_menu' => \App\Http\Middleware\CheckProofingMenu::class,
            'school_context' => \App\Http\Middleware\EnsureSchoolContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The "corrupt data when trying to hydrate a component" error does not say which page or
        // component hit it. Log that context (plus a short fingerprint of APP_KEY and the host name,
        // to spot servers running with different keys) so the cause can be traced.
        $exceptions->report(function (\Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException $e) {
            try {
                $request = request();
                $components = collect((array) $request->input('components', []))
                    ->map(function ($component) {
                        $snapshot = is_array($component) ? ($component['snapshot'] ?? null) : null;
                        $decoded = is_string($snapshot) ? json_decode($snapshot, true) : null;
                        return $decoded['memo']['name'] ?? 'unknown';
                    })
                    ->values()
                    ->all();

                \Illuminate\Support\Facades\Log::warning('Livewire corrupt payload context', [
                    'url' => $request->fullUrl(),
                    'referer' => $request->headers->get('referer'),
                    'user_id' => optional($request->user())->id,
                    'components' => $components,
                    'user_agent' => $request->userAgent(),
                    'app_key_fingerprint' => substr(hash('sha256', (string) config('app.key')), 0, 8),
                    'server' => gethostname(),
                    'php_version' => PHP_VERSION,
                    'serialize_precision' => ini_get('serialize_precision'),
                ]);
            } catch (\Throwable $ignored) {
                // never let diagnostics break error handling
            }
        });
    })->create();

    