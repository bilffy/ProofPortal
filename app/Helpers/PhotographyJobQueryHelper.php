<?php

namespace App\Helpers;

use App\Services\Proofing\StatusService;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class PhotographyJobQueryHelper
{
    /**
     * Restrict jobs to those eligible for Photography configure / portraits:
     * - proofing jobs (show_proofing = 1) are only included when archived
     *
     * @param  QueryBuilder|EloquentBuilder  $query
     */
    public static function applyDownloadAvailableJobFilter(QueryBuilder|EloquentBuilder $query, string $jobsTable = 'jobs'): void
    {
        $archivedId = app(StatusService::class)->archived;
        $prefix = $jobsTable === '' ? '' : "{$jobsTable}.";

        $query->where(function ($q) use ($archivedId, $prefix) {
            $q->whereNull("{$prefix}show_proofing")
                ->orWhere("{$prefix}show_proofing", '!=', 1)
                ->orWhere("{$prefix}job_status_id", $archivedId);
        });
    }
}
