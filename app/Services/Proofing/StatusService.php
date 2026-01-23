<?php

namespace App\Services\Proofing;

use App\Models\Status;

class StatusService
{
    // 1. Declare properties to avoid the "Dynamic Property" warning
    public $new, $invited, $active, $deleted, $inactive, $disabled, $sync, $unsync;
    public $review, $success, $duplicate, $error, $pending, $none, $completed;
    public $archived, $modified, $viewed, $hold, $locked, $unlocked, $rejected;
    public $incomplete, $autoApproved, $awaitingApproval, $approved, $tnjNotFound, $expired, $emailSent;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        // 2. OPTIMIZATION: Get all statuses in ONE single query instead of 28
        $statuses = Status::all()->pluck('id', 'status_internal_name');

        // 3. Map the database results to class properties
        $this->new              = $statuses->get('NEW');
        $this->invited          = $statuses->get('INVITED');
        $this->active           = $statuses->get('ACTIVE');
        $this->deleted          = $statuses->get('DELETED');
        $this->inactive         = $statuses->get('INACTIVE');
        $this->disabled         = $statuses->get('DISABLED');
        $this->sync             = $statuses->get('SYNC');
        $this->unsync           = $statuses->get('UNSYNC');
        $this->review           = $statuses->get('REVIEW');
        $this->success          = $statuses->get('SUCCESS');
        $this->duplicate        = $statuses->get('DUPLICATE');
        $this->error            = $statuses->get('ERROR');
        $this->pending          = $statuses->get('PENDING');
        $this->none             = $statuses->get('NONE');
        $this->completed        = $statuses->get('COMPLETED');
        $this->archived         = $statuses->get('ARCHIVED');
        $this->modified         = $statuses->get('MODIFIED');
        $this->viewed           = $statuses->get('VIEWED');
        $this->hold             = $statuses->get('HOLD');
        $this->locked           = $statuses->get('LOCKED');
        $this->unlocked         = $statuses->get('UNLOCKED');
        $this->rejected         = $statuses->get('REJECTED');
        $this->incomplete       = $statuses->get('INCOMPLETE');
        $this->autoApproved     = $statuses->get('AUTO APPROVED');
        $this->awaitingApproval = $statuses->get('AWAITING APPROVAL');
        $this->approved         = $statuses->get('APPROVED');
        $this->tnjNotFound      = $statuses->get('TNJ NOT FOUND');
        $this->expired          = $statuses->get('EXPIRED');
        $this->emailSent          = $statuses->get('EMAIL SENT');
    }

    public function InsertStatus($status_name)
    {
        // Logic cleanup: Ensure you use the right key names
        $internalName = strtoupper($status_name); 
        
        $status = Status::firstOrCreate(
            ['status_internal_name' => $internalName]
        );

        if ($status->wasRecentlyCreated) {
            return response()->json(['message' => 'Status added successfully'], 200);
        }

        return response()->json(['message' => 'Status already exists'], 500);
    }

    public function getAllStatusData(...$selectedValues)
    {
        return Status::select($selectedValues);
    }

    public function getDataById($IdValues)
    {
        return Status::whereIn('id', $IdValues);
    }
}