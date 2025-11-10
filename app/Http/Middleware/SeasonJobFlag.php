<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\TimestoneTableService;

class SeasonJobFlag
{
    protected $seasonService;
    protected $timestoneTableService;

    public function __construct(SeasonService $seasonService, TimestoneTableService $timestoneTableService)
    {
        $this->seasonService = $seasonService;
        $this->timestoneTableService = $timestoneTableService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Forget the job-season-flag if NOT on openSeason route
        if (! $request->routeIs('dashboard.openSeason')) {
            $request->session()->forget('job-season-flag');
        }

        if ($request->routeIs('dashboard.viewSeason') && $request->session()->get('openSeason') === true) {
            $selectedSeason = $request->session()->get('selectedSeasonDashboard');

            if (! $selectedSeason) {
                Log::warning('SeasonJobFlag: selectedSeason missing, skipping redirect.');
                return $next($request);
            }
        
            $encryptedSeason = Crypt::encryptString($selectedSeason['ts_season_id']);
            return redirect()->route('dashboard.openSeason', ['selectedSeasonId' => $encryptedSeason]);
        }        

        return $next($request);
    }
}
