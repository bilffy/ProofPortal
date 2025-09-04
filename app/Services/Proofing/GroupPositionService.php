<?php

namespace App\Services\Proofing;
use App\Models\GroupPosition;

class GroupPositionService
{
    public function updateGroupPosition($jobKey, $folderKey, $currentfirstname, $currentlastname, $newfirstname, $newlastname){
        return GroupPosition::where([
            ['ts_jobkey', $jobKey],
            ['ts_folderkey', $folderKey],
            ['subject_full_name', $currentfirstname .' '. $currentlastname]
        ])->update(['subject_full_name' => $newfirstname .' '. $newlastname]);
    }

    public function deleteGroupPosition($folderkey){
        return GroupPosition::where('ts_folderkey', $folderkey)->delete();
    }

    public function createGroupPosition($jobKey, $folderkey, $subjectkey, $fullname, $modifiedRowLabel, $rowNumber, $rowPosition){
        return GroupPosition::create([
            'ts_jobkey' => $jobKey, // Access as an array
            'ts_folderkey' => $folderkey, // Access as an array
            'ts_subjectkey' => $subjectkey ?? '',
            'subject_full_name' => $fullname ?? '',
            'row_description' => $modifiedRowLabel,
            'row_number' => $rowNumber, // Loop number of $jsonData
            'row_position' => $rowPosition, // Assuming row_position starts from 1
        ]);
    }

}
        