<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\SchoolContextHelper;

trait BelongsToFranchise
{
    /**
     * Boot the trait. Laravel calls this automatically.
     */
    public static function bootBelongsToFranchise()
    {
        static::addGlobalScope('franchise_school_access', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                
                // Get ts_account_id linked to the user's franchise_id
                $tsAccountId = $user->getFranchise()->ts_account_id;

                $school = SchoolContextHelper::getSchool();
                $userSchoolKey = $school->schoolkey ?? '';

                // If no account ID is found, deny access by default
                if (!$tsAccountId) {
                    return $builder->whereRaw('1 = 0');
                }

                // Check if the current model is the Job model
                // We use getMorphClass or a direct check to avoid namespace issues
                $model = $builder->getModel();
                
                if ($model instanceof \App\Models\Job) {
                    // Scenario A: Filtering the 'jobs' table directly
                    $builder->where($model->getTable() . '.ts_account_id', $tsAccountId)
                            ->where($model->getTable() . '.ts_schoolkey', $userSchoolKey);
                } else {
                    // Scenario B: Filtering related models
                    // We try 'job' first, then 'jobs'
                    $relation = method_exists($model, 'job') ? 'job' : (method_exists($model, 'jobs') ? 'jobs' : null);
                    
                    if ($relation) {
                        $builder->whereHas($relation, function ($query) use ($tsAccountId, $userSchoolKey) {
                            $query->where('ts_account_id', $tsAccountId)
                                  ->where('ts_schoolkey', $userSchoolKey);
                        });
                    }
                }
            }
        });
    }
}