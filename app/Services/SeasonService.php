<?php

namespace App\Services;
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
        return Season::select($selectedValues);
    }
}
