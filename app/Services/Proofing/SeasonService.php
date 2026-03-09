<?php

namespace App\Services\Proofing;
use App\Models\Season;

class SeasonService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllSeasonData(...$selectedValues)
    {
        return Season::select($selectedValues)
            ->where('is_default', 1);
    }

    public function getSeasonByTimestoneSeasonId($seasonId){
        return Season::where('ts_season_id', $seasonId);
    }
}
