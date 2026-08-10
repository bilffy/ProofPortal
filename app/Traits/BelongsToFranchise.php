<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
                $schoolId = $school->id ?? null;
                $userSchoolKey = $school->schoolkey ?? '';

                // If no account ID is found, deny access by default
                if (!$tsAccountId) {
                    return $builder->whereRaw('1 = 0');
                }

                $model = $builder->getModel();

                if ($model instanceof \App\Models\Job) {
                    $table = $model->getTable();
                    $builder->where($table . '.ts_account_id', $tsAccountId);

                    if ($schoolId) {
                        // Owned by this school, or unassigned Timestone jobs matching schoolkey
                        // (so Configure can list them before school_id is stamped on select).
                        $builder->where(function (Builder $query) use ($table, $schoolId, $userSchoolKey) {
                            $query->where($table . '.school_id', $schoolId);
                            if ($userSchoolKey !== '') {
                                $query->orWhere(function (Builder $unassigned) use ($table, $userSchoolKey) {
                                    $unassigned->whereNull($table . '.school_id')
                                        ->where($table . '.ts_schoolkey', $userSchoolKey);
                                });
                            }
                        });
                    }
                } else {
                    $relation = method_exists($model, 'job') ? 'job' : (method_exists($model, 'jobs') ? 'jobs' : null);

                    if ($relation) {
                        $builder->whereHas($relation, function ($query) use ($tsAccountId, $schoolId, $userSchoolKey) {
                            $query->where('ts_account_id', $tsAccountId);
                            if ($schoolId) {
                                $query->where(function ($q) use ($schoolId, $userSchoolKey) {
                                    $q->where('school_id', $schoolId);
                                    if ($userSchoolKey !== '') {
                                        $q->orWhere(function ($unassigned) use ($userSchoolKey) {
                                            $unassigned->whereNull('school_id')
                                                ->where('ts_schoolkey', $userSchoolKey);
                                        });
                                    }
                                });
                            }
                        });
                    }
                }
            }
        });
    }
}
