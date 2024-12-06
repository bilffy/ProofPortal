<?php

namespace App\Services;
use App\Models\School;

class SchoolService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    
    public function getSchoolBySchoolKey($schoolKey){
        return School::with('details')->where('schoolkey', $schoolKey);
    }

    public function saveSchoolData($decryptedSchoolKey, $field, $data){
        $school = School::where('schoolkey',$decryptedSchoolKey)->first();
        if($school) {
            $school->$field = $data;
            $school->save();
        }
    }
}
