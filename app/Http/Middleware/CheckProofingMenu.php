<?php

namespace App\Http\Middleware;

use App\Helpers\AppSettingsHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProofingMenu
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $proofingMenu = AppSettingsHelper::getByPropertyKey('proofing_menu');
        
        // Default to true if setting doesn't exist, otherwise must be explicitly 'true'
        $proofingMenuValue = $proofingMenu ? ($proofingMenu->property_value === 'true') : true;

        if (!$proofingMenuValue) {
            abort(403, 'Proofing module is currently disabled.');
        }

        return $next($request);
    }
}
