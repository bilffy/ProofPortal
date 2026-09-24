<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class PhotographyJobQueryHelper
{
    /**
     * Restrict jobs to those eligible for Photography configure / portraits:
     * - only jobs with a download_available_date set are included
     *
     * @param  QueryBuilder|EloquentBuilder  $query
     */
    public static function applyDownloadAvailableJobFilter(QueryBuilder|EloquentBuilder $query, string $jobsTable = 'jobs'): void
    {
        $prefix = $jobsTable === '' ? '' : "{$jobsTable}.";

        $query->whereNotNull("{$prefix}download_available_date");
    }
}
