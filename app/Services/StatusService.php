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


        // $active = Status::where('status_internal_name','ACTIVE')->select('status_id')->first();

        // $inactive =  Status::where('status_internal_name','INACTIVE')->select('status_id')->first();
        // $sync =  Status::where('status_internal_name','SYNC')->select('status_id')->first();
        // $unsync =  Status::where('status_internal_name','UNSYNC')->select('status_id')->first();
        // $review =  Status::where('status_internal_name','REVIEW')->select('status_id')->first();
        // $pending =  Status::where('status_internal_name','PENDING')->select('status_id')->first();
        // $success =  Status::where('status_internal_name','SUCCESS')->select('status_id')->first();
        // $duplicate =  Status::where('status_internal_name','DUPLICATE')->select('status_id')->first();
        // $completed =  Status::where('status_internal_name','COMPLETED')->select('status_id')->first();
        // $error =  Status::where('status_internal_name','ERROR')->select('status_id')->first();

        // $none = Status::where('status_internal_name','NONE')->select('status_id')->first();

        // $archived = Status::where('status_internal_name','ARCHIVED')->select('status_id')->first();
        // $modified = Status::where('status_internal_name','MODIFIED')->select('status_id')->first();
        // $viewed = Status::where('status_internal_name','VIEWED')->select('status_id')->first();

        // $locked = Status::where('status_internal_name','LOCKED')->select('status_id')->first();
        // $unlocked = Status::where('status_internal_name','UNLOCKED')->select('status_id')->first();
        // $rejected = Status::where('status_internal_name','REJECTED')->select('status_id')->first();
        // $incomplete = Status::where('status_internal_name','INCOMPLETE')->select('status_id')->first();
        // $autoApproved = Status::where('status_internal_name','AUTO APPROVED')->select('status_id')->first();
        // $awaitingApproval = Status::where('status_internal_name','AWAITING APPROVAL')->select('status_id')->first();
        // $approved = Status::where('status_internal_name','APPROVED')->select('status_id')->first();
        // $tnjNotFound = Status::where('status_internal_name','TNJ NOT FOUND')->select('status_id')->first();

        // if($active){$this->active = $active->status_id;}
        // if($inactive){$this->inactive = $inactive->status_id;}
        // if($sync){$this->sync = $sync->status_id;}
        // if($unsync){$this->unsync = $unsync->status_id;}
        // if($review){$this->review = $review->status_id;}
        // if($pending){$this->pending = $pending->status_id;}
        // if($success){$this->success = $success->status_id;}
        // if($duplicate){$this->duplicate = $duplicate->status_id;}
        // if($completed){$this->completed = $completed->status_id;}
        // if($error){$this->error = $error->status_id;}
        // if($archived){$this->archived = $archived->status_id;}
        // if($none){$this->none = $none->status_id;}
        // if($viewed){$this->viewed = $viewed->status_id;}
        // if($modified){$this->modified = $modified->status_id;}
        // if($locked){$this->locked = $locked->status_id;}
        // if($unlocked){$this->unlocked = $unlocked->status_id;}
        // if($incomplete){$this->incomplete = $incomplete->status_id;}
        // if($rejected){$this->rejected = $rejected->status_id;}
        // if($approved){$this->approved = $approved->status_id;}
        // if($autoApproved){$this->autoApproved = $autoApproved->status_id;}
        // if($awaitingApproval){$this->awaitingApproval = $awaitingApproval->status_id;}
        // if($tnjNotFound){$this->tnjNotFound = $tnjNotFound->status_id;}
        

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

    public function InsertStatus($status_name)
    {
        $data = [
            'status_internal_name' => ucfirst($status_name),
            'status_external_name' => ucfirst($status_name)
        ];
        $bpStatusTable = Status::where('status_internal_name', $data['status_internal_name'])->firstOrNew($data);

        if(!$bpStatusTable->exists){
            $status = new Status;
            $status->status_internal_name = $data['status_internal_name'];
            $status->status_external_name = $data['status_external_name'];
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

}
