<?php

namespace App\Services\Proofing;
use App\Models\ProofingIssue;
use App\Services\Proofing\StatusService;

class ProofingDescriptionService
{
    protected $statusService;

    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function getAllProofingDescriptionData($type,...$selectedValues)
    {
        return ProofingIssue::whereHas('issuecategory', function ($query) use ($type) {
            $query->where('category_name', $type);
        })->select($selectedValues)
          ->get();
    }

    public function getAllProofingDescriptionByDescription($description,...$selectedValues)
    {
        return ProofingIssue::where('issue_description', $description)->select($selectedValues)->first();
    }

    public function getAllProofingDescriptionById($id,...$selectedValues){
        return ProofingIssue::where('id', $id)->select($selectedValues)->first();
    }

    public function getAllProofingDescriptionByIssueName($issueName,...$selectedValues){
        return ProofingIssue::where('issue_name', $issueName)->select($selectedValues)->first();
    }
}
