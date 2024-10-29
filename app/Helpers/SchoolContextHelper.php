<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use App\Models\Franchise;
use App\Models\School;

class SchoolContextHelper
{
    // Fetch list of schools by a given franchise
    public static function getSchoolsByFranchise(Franchise $franchise): array
    {
        $schools = School::query()
            ->leftJoin('school_franchises', 'schools.id', '=', 'school_franchises.school_id')
            ->leftJoin('franchises', 'school_franchises.franchise_id', '=', 'franchises.id')
            ->select('schools.*', 'franchises.name as franchise_name')
            ->where('franchises.id', $franchise->id)
            ->orderBy('schools.name', 'ASC');
        
        return $schools->get()->toArray();
    }
    
    public static function isSchoolContext(): bool
    {
        return Session::has('school_context-sid');
    }

    public static function getCurrentSchoolContext(): School|null
    {
        if (self::isSchoolContext()) {
            return School::find(Session::get('school_context-sid'));
        }
    
        return null;
    }
    
    public static function removeSchoolContext(): void
    {
        Session::forget('school_context-sid');
    }
    
}