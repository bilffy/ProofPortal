<?php

namespace App\Services;
use App\Models\Status;

class StatusService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {

        $active = Status::where('status_internal_name','ACTIVE')->select('id')->first();
        
        $inactive =  Status::where('status_internal_name','INACTIVE')->select('id')->first();
        $sync =  Status::where('status_internal_name','SYNC')->select('id')->first();
        $unsync =  Status::where('status_internal_name','UNSYNC')->select('id')->first();
        $review =  Status::where('status_internal_name','REVIEW')->select('id')->first();
        $pending =  Status::where('status_internal_name','PENDING')->select('id')->first();
        $success =  Status::where('status_internal_name','SUCCESS')->select('id')->first();
        $duplicate =  Status::where('status_internal_name','DUPLICATE')->select('id')->first();
        $completed =  Status::where('status_internal_name','COMPLETED')->select('id')->first();
        $error =  Status::where('status_internal_name','ERROR')->select('id')->first();

        $none = Status::where('status_internal_name','NONE')->select('id')->first();

        $archived = Status::where('status_internal_name','ARCHIVED')->select('id')->first();
        $modified = Status::where('status_internal_name','MODIFIED')->select('id')->first();
        $viewed = Status::where('status_internal_name','VIEWED')->select('id')->first();

        $locked = Status::where('status_internal_name','LOCKED')->select('id')->first();
        $unlocked = Status::where('status_internal_name','UNLOCKED')->select('id')->first();
        $rejected = Status::where('status_internal_name','REJECTED')->select('id')->first();
        $incomplete = Status::where('status_internal_name','INCOMPLETE')->select('id')->first();
        $autoApproved = Status::where('status_internal_name','AUTO APPROVED')->select('id')->first();
        $awaitingApproval = Status::where('status_internal_name','AWAITING APPROVAL')->select('id')->first();
        $approved = Status::where('status_internal_name','APPROVED')->select('id')->first();
        $tnjNotFound = Status::where('status_internal_name','TNJ NOT FOUND')->select('id')->first();

        if($active){$this->active = $active->id;}
        if($inactive){$this->inactive = $inactive->id;}
        if($sync){$this->sync = $sync->id;}
        if($unsync){$this->unsync = $unsync->id;}
        if($review){$this->review = $review->id;}
        if($pending){$this->pending = $pending->id;}
        if($success){$this->success = $success->id;}
        if($duplicate){$this->duplicate = $duplicate->id;}
        if($completed){$this->completed = $completed->id;}
        if($error){$this->error = $error->id;}
        if($archived){$this->archived = $archived->id;}
        if($none){$this->none = $none->id;}
        if($viewed){$this->viewed = $viewed->id;}
        if($modified){$this->modified = $modified->id;}
        if($locked){$this->locked = $locked->id;}
        if($unlocked){$this->unlocked = $unlocked->id;}
        if($incomplete){$this->incomplete = $incomplete->id;}
        if($rejected){$this->rejected = $rejected->id;}
        if($approved){$this->approved = $approved->id;}
        if($autoApproved){$this->autoApproved = $autoApproved->id;}
        if($awaitingApproval){$this->awaitingApproval = $awaitingApproval->id;}
        if($tnjNotFound){$this->tnjNotFound = $tnjNotFound->id;}
    }

    public function InsertStatus($status_name){
        $data = [
            'status_internal_name' => ucfirst($status_name)
        ];
        $bpStatusTable = Status::where('status_internal_name', $data['name'])->firstOrNew($data);

        if(!$bpStatusTable->exists){
            $status = new Status;
            $status->status_internal_name = $data['name'];
            $status->save();
            return response()->json([
            'message' => 'Status added successfully',
            ], 200);
        }else{
            return response()->json([
            'message' => 'Status already exists',
            ], 500); 

        }
    }

    public function getAllStatusData(...$selectedValues)
    {
        return Status::select($selectedValues);
    }

    public function getDataById($IdValues){
        return Status::whereIn('id', $IdValues);
    }

}
