<?php

namespace App\Services\Proofing;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\JobService;
use App\Services\Proofing\FolderService;
use App\Services\Proofing\SubjectService;
use App\Services\Proofing\ImageService;
use App\Services\Proofing\TimestoneTableService;
use App\Services\Proofing\ProofingChangelogService;
use App\Services\Proofing\FolderSubjectService;
use App\Services\Proofing\EmailService;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\DB;
use App\Jobs\SyncImagesToProd02;
use App\Models\FolderSubject;
use App\Models\Image;

class ConfigureService
{
    protected $encryptDecryptService;
    protected $jobService;
    protected $folderService;
    protected $subjectService;
    protected $imageService;
    protected $proofingChangelogService;
    protected $timestoneTableService;
    protected $folderSubjectService;
    protected $emailService;
    protected $statusService;

    public function __construct(
        EncryptDecryptService $encryptDecryptService, JobService $jobService, FolderService $folderService, ProofingChangelogService $proofingChangelogService,
        SubjectService $subjectService, ImageService $imageService, TimestoneTableService $timestoneTableService, FolderSubjectService $folderSubjectService, 
        EmailService $emailService, StatusService $statusService
        )
    {
        $this->encryptDecryptService = $encryptDecryptService;
        $this->jobService = $jobService;
        $this->folderService = $folderService;
        $this->subjectService = $subjectService;
        $this->imageService = $imageService;
        $this->proofingChangelogService = $proofingChangelogService;
        $this->timestoneTableService = $timestoneTableService;
        $this->folderSubjectService = $folderSubjectService;
        $this->emailService = $emailService;
        $this->statusService = $statusService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function insertProofingTimeline($data)
    {
        $tsJobKey = $this->getDecryptData($data['jobHash']);
        $dataType = $data['dataType'] ?? null;
        $date = $data['date'] ?? null;

        if ($dataType === 'proof_start') {
            $validationError = $this->validateProofStartAgainstWarningAndDue($tsJobKey, $date);
            if ($validationError !== null) {
                return [
                    'success' => false,
                    'message' => $validationError,
                ];
            }
        }

        $this->jobService->updateJobData($tsJobKey, $dataType, $date);

        if ($dataType === 'proof_catchup') {
            $this->jobService->updateJobData($tsJobKey, 'is_in_catchup', 0);
        }

        // Keep {REVIEW_DUE} in pending start/warning/catchup emails in sync with the new due date
        if ($dataType === 'proof_due') {
            $this->emailService->refreshReviewDueInPendingProofEmails($tsJobKey, $date);
        }

        // After Start or Due is saved: activate when Due is after Start, both future, status not none.
        if (in_array($dataType, ['proof_start', 'proof_due'], true)) {
            $this->activateJobIfFutureTimeline($tsJobKey);
        }

        return ['success' => true];
    }

    /**
     * When Start Date is set, Warning/Due must be empty or after Start Date.
     */
    protected function validateProofStartAgainstWarningAndDue(string $tsJobKey, $startDate): ?string
    {
        if (empty($startDate)) {
            return null;
        }

        $job = $this->jobService->getJobByJobKey($tsJobKey)
            ->select('proof_warning', 'proof_due')
            ->first();

        if (!$job) {
            return null;
        }

        $start = \Carbon\Carbon::parse($startDate);
        $warningBeforeStart = !empty($job->proof_warning)
            && \Carbon\Carbon::parse($job->proof_warning)->lte($start);
        $dueBeforeStart = !empty($job->proof_due)
            && \Carbon\Carbon::parse($job->proof_due)->lte($start);

        if ($warningBeforeStart || $dueBeforeStart) {
            return 'Please reset the Warning Date and the Due Date as it should be after the Start Date';
        }

        return null;
    }

    /**
     * If status is not "none", Due is after Start, and both dates are in the future → set Active.
     * Runs after Start Date or Due Date is saved.
     */
    protected function activateJobIfFutureTimeline(string $tsJobKey): void
    {
        $job = $this->jobService->getJobByJobKey($tsJobKey)
            ->select('ts_job_id', 'ts_jobkey', 'job_status_id', 'proof_start', 'proof_due')
            ->first();

        if (!$job || empty($job->proof_start) || empty($job->proof_due)) {
            return;
        }

        $noneStatusId = $this->statusService->none;
        if ((int) $job->job_status_id === (int) $noneStatusId) {
            return;
        }

        $start = \Carbon\Carbon::parse($job->proof_start);
        $due = \Carbon\Carbon::parse($job->proof_due);
        $now = \Carbon\Carbon::now();

        if ($due->greaterThan($start) && $start->greaterThan($now) && $due->greaterThan($now)) {
            $this->jobService->updateJobStatus((int) $job->ts_job_id, $this->statusService->active);
        }
    }

    public function sendEmailDates($data){
        $tsJobKey = $this->getDecryptData($data['jobHash']);
        if ($data['dataType'] === 'proof_start' || $data['dataType'] === 'proof_warning' || $data['dataType'] === 'proof_due'|| $data['dataType'] === 'proof_catchup') {
            // Do not queue emails for an invalid Start Date that was rejected on submit.
            if (($data['dataType'] ?? null) === 'proof_start') {
                $validationError = $this->validateProofStartAgainstWarningAndDue($tsJobKey, $data['date'] ?? null);
                if ($validationError !== null) {
                    return [
                        'success' => false,
                        'message' => $validationError,
                    ];
                }
            }

            $saveEmailContent = $this->emailService->saveEmailContent($tsJobKey, $data['dataType'], $data['date'], null);

            // Also refresh REVIEW_DUE inside other pending proof schedule emails for this job
            if ($data['dataType'] === 'proof_due') {
                $this->emailService->refreshReviewDueInPendingProofEmails($tsJobKey, $data['date']);
            }
        }

        return ['success' => true];
    }

    public function mergeDuplicateFolders($tsJobId){
                
        $duplicateFolders = $this->folderService->getFolderByJobId($tsJobId)
            ->select('ts_folderkey','teacher', 'principal', 'deputy', DB::raw('count(*) as total'))
            ->groupBy('ts_folderkey')
            ->having('total', '>', 1)
            ->get();

        if ($duplicateFolders->isEmpty()) {
            return;
        }

        $foldersByKey = $this->folderService->getAllFolderAssociationsByKeys(
            $duplicateFolders->pluck('ts_folderkey')->all()
        );
        
        $deleteFolders = 0;
        $deletedSubjects = 0;
        $deletedImages = 0;
        $deletedAttachedSubjects = 0;
        $deletedTraditionalPhoto = 0;
        
        foreach ($duplicateFolders as $duplicateFolder) {
            $folders = $foldersByKey->get($duplicateFolder->ts_folderkey, collect());
            if ($folders->isEmpty()) {
                continue;
            }

            $keepFolder = $folders->first();
            
            // Collect IDs for bulk deletes
            $attachedSubjectIdsToDelete = [];
            $changelogIdsToDelete = [];
            $attachedSubjectIdsToReassign = [];

            foreach ($folders as $folder) {
                if ($folder->id !== $keepFolder->id) {
                    // Update principal, deputy, teacher data
                    if(isset($folder->principal) && is_null($keepFolder->principal)){
                        $this->folderService->updatePrincipal($keepFolder, $folder->principal);
                    }
                    if(isset($folder->deputy) && is_null($keepFolder->deputy)){
                        $this->folderService->updateDeputy($keepFolder, $folder->deputy);
                    }
                    if(isset($folder->teacher) && is_null($keepFolder->teacher)){
                        $this->folderService->updateTeacher($keepFolder, $folder->teacher);
                    }

                    // 1. Merge attached subjects (in-memory filter; bulk reassign below)
                    $keepSubjectIds = $keepFolder->subjects->pluck('ts_subject_id')->flip();
                    foreach ($folder->attachedsubjects as $attachedSubject) {
                        if (!isset($keepSubjectIds[$attachedSubject->ts_subject_id])) {
                            $attachedSubjectIdsToReassign[] = $attachedSubject->id;
                        }
                    }

                    // Collect attachedSubject IDs for deletion
                    foreach ($folder->attachedsubjects->groupBy('ts_subject_id') as $attachedSubjectGroup) {
                        $attachedSubjectGroup->shift(); // Keep the first attachedSubject
                        $attachedSubjectIdsToDelete = array_merge($attachedSubjectIdsToDelete, $attachedSubjectGroup->pluck('id')->toArray());
                        $deletedAttachedSubjects++;
                    }

                    // 2. Merge proofing changelogs and delete duplicates (use eager-loaded relations)
                    $keepTraditionalKeyvalues = $keepFolder->proofingChangelogs
                        ->filter(fn ($changelog) => str_starts_with((string) $changelog->notes, 'Traditional Photo People Row Positions for Folder'))
                        ->pluck('keyvalue')
                        ->flip();

                    $changelogToMerge = $folder->proofingChangelogs
                        ->filter(fn ($changelog) => str_starts_with((string) $changelog->notes, 'Traditional Photo People Row Positions for Folder'))
                        ->reject(fn ($changelog) => isset($keepTraditionalKeyvalues[$changelog->keyvalue]));

                    if ($changelogToMerge->isNotEmpty()) {
                        foreach ($changelogToMerge as $changelog) {
                            $keepFolder->proofingChangelogs()->save($changelog);
                        }
                    }

                    // Collect changelog IDs for bulk deletion
                    foreach ($folder->proofingChangelogs->groupBy('keyvalue') as $changelogGroup) {
                        $changelogGroup->shift(); // Keep the first changelog
                        $changelogIdsToDelete = array_merge($changelogIdsToDelete, $changelogGroup->pluck('id')->toArray());
                        $deletedTraditionalPhoto++;
                    }

                    $folder->delete(); // Delete the duplicate folder
                    $deleteFolders++;
                }
            }

            if ($attachedSubjectIdsToReassign !== []) {
                FolderSubject::whereIn('id', $attachedSubjectIdsToReassign)
                    ->update(['ts_folder_id' => $keepFolder->ts_folder_id]);
            }

            // Bulk delete attached subjects, and changelogs
            if (!empty($attachedSubjectIdsToDelete)) {
                $this->folderSubjectService->deleteFolderSubject($attachedSubjectIdsToDelete);
            }
            if (!empty($changelogIdsToDelete)) {
                $this->proofingChangelogService->deleteChangelog($changelogIdsToDelete);
            }
        }
    }

    public function mergeDuplicateSubjects($tsJobId)
    {
        $duplicateSubjects = $this->subjectService->getSubjectByJobId($tsJobId)
            ->select('ts_subjectkey', DB::raw('count(*) as total'))
            ->groupBy('ts_subjectkey')
            ->having('total', '>', 1)
            ->get();

        if ($duplicateSubjects->isEmpty()) {
            return;
        }

        $subjectsByKey = $this->subjectService->getAllSubjectAssociationsByKeys(
            $duplicateSubjects->pluck('ts_subjectkey')->all()
        );
    
        $deletedSubjects = 0;
        $deletedImages = 0;
        $deletedAttachedSubjects = 0;

        foreach ($duplicateSubjects as $duplicateSubject) {
            $subjects = $subjectsByKey->get($duplicateSubject->ts_subjectkey, collect());
            if ($subjects->isEmpty()) {
                continue;
            }
            
            $keepSubject = $subjects->first();

            $imagesToDelete = [];
            $attachedSubjectsToDelete = [];
            $imageIdsToReassign = [];
            $attachedSubjectIdsToReassign = [];

            $keepImageKeys = Image::query()
                ->where('keyvalue', $keepSubject->ts_subjectkey)
                ->pluck('ts_imagekey')
                ->flip();
            $keepAttachedSubjectIds = $keepSubject->attachedsubjects->pluck('ts_subject_id')->flip();

            foreach ($subjects as $subject) {
                if ($subject->id === $keepSubject->id) {
                    continue;
                }

                $subjectImages = Image::query()
                    ->where('keyvalue', $subject->ts_subjectkey)
                    ->get();

                foreach ($subjectImages as $image) {
                    if (!isset($keepImageKeys[$image->ts_imagekey])) {
                        $imageIdsToReassign[] = $image->id;
                        $keepImageKeys[$image->ts_imagekey] = true;
                    }
                }

                foreach ($subjectImages->groupBy('ts_imagekey') as $imageGroup) {
                    $imagesToDelete = array_merge($imagesToDelete, $imageGroup->slice(1)->pluck('id')->toArray());
                }

                foreach ($subject->attachedsubjects as $attachedSubject) {
                    if (!isset($keepAttachedSubjectIds[$attachedSubject->ts_subject_id])) {
                        $attachedSubjectIdsToReassign[] = $attachedSubject->id;
                        $keepAttachedSubjectIds[$attachedSubject->ts_subject_id] = true;
                    }
                }

                foreach ($subject->attachedsubjects->groupBy(function ($attachedSubject) {
                    return $attachedSubject->ts_folder_id . '-' . $attachedSubject->ts_subject_id;
                }) as $attachedSubjectGroup) {
                    $attachedSubjectsToDelete = array_merge($attachedSubjectsToDelete, $attachedSubjectGroup->slice(1)->pluck('id')->toArray());
                }

                $subject->delete();
                $deletedSubjects++;
            }

            if ($imageIdsToReassign !== []) {
                Image::whereIn('id', $imageIdsToReassign)
                    ->update(['keyvalue' => $keepSubject->ts_subjectkey]);
            }

            if ($attachedSubjectIdsToReassign !== []) {
                FolderSubject::whereIn('id', $attachedSubjectIdsToReassign)
                    ->update(['ts_subject_id' => $keepSubject->ts_subject_id]);
            }

            $this->imageService->deleteImage($imagesToDelete);
            $this->folderSubjectService->deleteFolderSubject($attachedSubjectsToDelete);

            $deletedImages += count($imagesToDelete);
            $deletedAttachedSubjects += count($attachedSubjectsToDelete); 
        }
    }

    public function updateSubjectAssociations($tsJobId)
    {
        $job = \App\Models\Job::where('ts_job_id', $tsJobId)->first();
        if (!$job || !$this->timestoneTableService->isJobEligibleForSync($job->ts_jobkey)) {
            return false;
        }

        // Timestone
        $tsSubjects = $this->timestoneTableService->getAllTimestoneSubjectsByJobID($tsJobId); // Format subjects by SubjectKey

        // Blueprint Folders (Pre-fetch all to eliminate N+1 lookup)
        $bpFolders = $this->folderService->getFolderByJobId($tsJobId)->pluck('ts_folder_id', 'ts_folderkey');

        // Blueprint Subjects
        $bpSubjects = $this->subjectService->getByJobId($tsJobId, 'id', 'ts_folder_id', 'ts_subjectkey', 'ts_subject_id')
                ->orderBy('ts_subjectkey', 'asc')
                ->get();

        $bpSubjectsUpdated = [];
        foreach ($bpSubjects as $bpSubject) {
            if (isset($tsSubjects[$bpSubject->ts_subjectkey])) {
                $folderKey = $tsSubjects[$bpSubject->ts_subjectkey]->FolderKey;

                // Memory map lookup instead of a database hit
                if (isset($bpFolders[$folderKey]) && $bpSubject->ts_folder_id != $bpFolders[$folderKey]) {
                    $bpSubject->ts_folder_id = $bpFolders[$folderKey];
                    $bpSubjectsUpdated[] = $bpSubject;
                }
            }
        }

        // Save the updated subjects natively
        foreach ($bpSubjectsUpdated as $updatedSubject) {
            $updatedSubject->save(); 
        }

        // Update FoldersSubjects table from Timestone using Bulk queries
        $this->recastTimestoneLinksOfSubjectsToFolders($bpSubjects->pluck('ts_subject_id'), $bpFolders->values()->toArray());

        $this->timestoneTableService->markBlueprintSyncComplete($job->ts_jobkey, $this->statusService->success);

        return true;
    }
    

    private function recastTimestoneLinksOfSubjectsToFolders($tsSubjectIds = null, $validBpFolderIds = [])
    {
        // Timestone
        $tsFolderSubjectPairs = $this->timestoneTableService->getAllTimestoneSubjectFolders($tsSubjectIds);

        // Blueprint
        $bpFolderSubjectPairs = $this->folderSubjectService->getAllBlueprintSubjectFolders($tsSubjectIds);
        
        $tsPairsGrouped = $tsFolderSubjectPairs->groupBy('SubjectID');
        $bpPairsGrouped = $bpFolderSubjectPairs->groupBy('ts_subject_id');

        $subjectsToDeleteLinks = [];
        $linksToInsert = [];

        // Check TS vs BP mappings (Iterate TS to catch entirely new mappings)
        foreach ($tsPairsGrouped as $subjectId => $tsPairs) {
            $tsFolderIds = $tsPairs->pluck('FolderID')->unique()->toArray();
            
            // Only attempt to link folders that actually currently exist securely in Blueprint
            if (!empty($validBpFolderIds)) {
                $tsFolderIds = array_values(array_intersect($tsFolderIds, $validBpFolderIds));
            }
            sort($tsFolderIds);

            if (isset($bpPairsGrouped[$subjectId])) {
                $bpFolderIds = $bpPairsGrouped[$subjectId]->pluck('ts_folder_id')->unique()->toArray();
                sort($bpFolderIds);

                // If identical, we don't need to do anything
                if ($tsFolderIds !== $bpFolderIds) {
                    $subjectsToDeleteLinks[] = $subjectId;
                    foreach ($tsFolderIds as $folderId) {
                        $linksToInsert[] = [
                            'ts_folder_id'  => $folderId,
                            'ts_subject_id' => $subjectId,
                            'is_deleted'    => 0,
                            'created_at'    => \Carbon\Carbon::now(),
                            'updated_at'    => \Carbon\Carbon::now()
                        ];
                    }
                }
                
                // Remove from bp array to evaluate stranded bp links
                unset($bpPairsGrouped[$subjectId]);
            } else {
                // Not in blueprint at all, need to insert all
                foreach ($tsFolderIds as $folderId) {
                    $linksToInsert[] = [
                        'ts_folder_id'  => $folderId,
                        'ts_subject_id' => $subjectId,
                        'is_deleted'    => 0,
                        'created_at'    => \Carbon\Carbon::now(),
                        'updated_at'    => \Carbon\Carbon::now()
                    ];
                }
            }
        }

        // Any remaining items in bpPairsGrouped have NO links in TS! They should be cleared completely.
        foreach ($bpPairsGrouped as $subjectId => $bpPairs) {
            $subjectsToDeleteLinks[] = $subjectId;
        }

        // Perform Massive Bulk Operations To DB

        if (!empty($subjectsToDeleteLinks)) {
            // Bulk delete all mismatched links
            foreach (array_chunk($subjectsToDeleteLinks, 1000) as $chunk) {
                \App\Models\FolderSubject::whereIn('ts_subject_id', $chunk)->delete();
            }
        }

        if (!empty($linksToInsert)) {
            // Bulk insert all proper links
            foreach (array_chunk($linksToInsert, 500) as $chunk) {
                \App\Models\FolderSubject::insert($chunk);
            }
        }
    }

    public function updatePeopleImage($tsJobId, $tsJobKey)
    {
        if (!$this->timestoneTableService->isJobEligibleForSync($tsJobKey)) {
            return false;
        }

        DB::beginTransaction();
    
        try {
            // Timestone (grouped by SubjectID, multiple images supported)
            $tsSubjectImages = $this->timestoneTableService
                ->getAllTimestoneHomeSubjectsImageByJobID($tsJobId);
    
            // Blueprint subjects (ARRAY-based)
            $bpSubjectImages = $this->subjectService
                ->getAllTimestoneHomeSubjectsByJobID($tsJobId);
    
            $this->updateImage($tsSubjectImages, $bpSubjectImages);

            // Re-flag the umbrella Job so the cron scheduler knows it needs attention as a fallback
            \App\Models\Job::where('ts_job_id', $tsJobId)->update(['imagesync_status_id' => $this->statusService->unsync]);
            
            DB::commit();

            $this->timestoneTableService->markBlueprintSyncComplete($tsJobKey, $this->statusService->success);

            // Fire queue ONLY after ALL nested database transactions officially record the changes on disk
            SyncImagesToProd02::dispatch($tsJobKey)->afterCommit();

            return true;
        } catch (\Throwable $e) {
            DB::rollBack(); // NOTHING SAVES if error occurs
            throw $e;
        }
    }    

    private function updateAttachedPeopleImage($tsJobId)
    {
        //Timestone
        $tsAttachedSubjectImages = $this->timestoneTableService->getAllTimestoneAttachedSubjectsImageByJobID($tsJobId)->get()->keyBy('SubjectID');

        //Blueprint
        $bpFolders = $this->folderService->getFolderByJobId($tsJobId)->pluck('ts_folder_id');
        $bpAttachedSubjectIDs = $this->folderSubjectService->getAllBlueprintSubjectFoldersByFolderIds($bpFolders->toArray());
        $bpAttachedSubjectImages = $this->folderSubjectService->getAllAttachedSubjectsImageBySubjectIds($bpAttachedSubjectIDs->pluck('ts_subject_id')->toArray());

        $this->updateImage($tsAttachedSubjectImages, $bpAttachedSubjectImages);
    }

    private function updateImage($tsSubjectImages, $bpSubjectImages)
    {
        // 1. Get all subject keys we care about
        $bpSubjectKeys = collect($bpSubjectImages)->pluck('ts_subjectkey')->filter()->unique()->toArray();
        if (empty($bpSubjectKeys)) return;

        // 2. Fetch ALL existing blueprint images for these subjects in ONE query to eliminate the N+1 problem.
        $existingBpImages = \App\Models\Image::where('keyorigin', 'Subject')
            ->whereIn('keyvalue', $bpSubjectKeys)
            ->get()
            ->groupBy('keyvalue'); // Fast hash-map locally grouped by subject key

        $imagesToInsert = [];
        $imagesToUpdate = [];
        $idsToDelete = [];

        foreach ($bpSubjectImages as $bpSubjectImage) {
            $tsSubjectId  = $bpSubjectImage['ts_subject_id'];
            $tsSubjectKey = $bpSubjectImage['ts_subjectkey'];

            // Get existing images for this subject from our pre-fetched collection
            $subjectExistingImages = $existingBpImages->get($tsSubjectKey, collect());
            $existingBpImageKeys = $subjectExistingImages->pluck('ts_imagekey')->toArray();

            // If no images exist in Timestone → mark all for deletion
            if (!isset($tsSubjectImages[$tsSubjectId])) {
                $idsToDelete = array_merge($idsToDelete, $subjectExistingImages->pluck('id')->toArray());
                continue;
            }

            $tsImages = $tsSubjectImages[$tsSubjectId];
            $tsImageKeys = [];
            $imageCount = count($tsImages ?? []);

            foreach ($tsImages as $tsImage) {
                $imageKey       = $tsImage->ImageKey;
                $imageID        = $tsImage->ImageID;
                $imageIsPrimary = $tsImage->IsPrimary;

                $tsImageKeys[] = $imageKey;

                if ($imageCount > 1) {
                    $dynamicExportStatus = ($imageIsPrimary == 1) ? 0 : null;
                } else {
                    $dynamicExportStatus = 0;
                }

                // Check if this image already exists in Blueprint
                $existingImage = $subjectExistingImages->firstWhere('ts_imagekey', $imageKey);

                if ($existingImage) {
                    $needsUpdate = false;

                    if ($existingImage->ts_image_id != $imageID) {
                        $existingImage->ts_image_id = $imageID;
                        $needsUpdate = true;
                    }
                    if ((int) $existingImage->is_primary !== (int) $imageIsPrimary) {
                        $existingImage->is_primary = $imageIsPrimary;
                        $needsUpdate = true;
                    }

                    // Always recalculate exportStatus from the *current* Timestone image set.
                    // Fixes leftover NULL on a former non-primary after siblings were removed.
                    $resolvedExportStatus = $this->resolvePeopleImageExportStatus(
                        $existingImage,
                        $dynamicExportStatus
                    );
                    if ($existingImage->exportStatus !== $resolvedExportStatus) {
                        $existingImage->exportStatus = $resolvedExportStatus;
                        $needsUpdate = true;
                    }

                    if ($needsUpdate) {
                        $imagesToUpdate[] = $existingImage;
                    }
                } else {
                    // Prepare for bulk insert for massive speed improvements
                    $imagesToInsert[] = [
                        'keyorigin'   => 'Subject',
                        'keyvalue'    => $tsSubjectKey,
                        'ts_imagekey' => $imageKey,
                        'ts_image_id' => $imageID,
                        'ts_job_id'   => $bpSubjectImage['ts_job_id'],
                        'is_primary'  => $imageIsPrimary,
                        'exportStatus'=> $dynamicExportStatus,
                        'protected'   => 0,
                        'created_at'  => \Carbon\Carbon::now(),
                        'updated_at'  => \Carbon\Carbon::now()
                    ];
                }
            }

            // DELETE images that no longer exist in Timestone for this subject
            $imagesToDelete = array_diff($existingBpImageKeys, $tsImageKeys);
            if (!empty($imagesToDelete)) {
                $toDelete = $subjectExistingImages->whereIn('ts_imagekey', $imagesToDelete)->pluck('id')->toArray();
                $idsToDelete = array_merge($idsToDelete, $toDelete);
            }
        }

        // --- Execute Bulk Operations ---

        // 1. Bulk Delete all old images across all subjects in chunks
        if (!empty($idsToDelete)) {
            foreach (array_chunk($idsToDelete, 1000) as $chunk) {
                \App\Models\Image::whereIn('id', $chunk)->delete();
            }
        }

        // 2. Bulk Insert all new images in chunks
        if (!empty($imagesToInsert)) {
            foreach (array_chunk($imagesToInsert, 500) as $chunk) {
                \App\Models\Image::insert($chunk);
            }
        }

        // 3. Save updates
        // Since we explicitly filtered out unmodified rows, this array is often completely empty!
        foreach ($imagesToUpdate as $imageModel) {
            $imageModel->save();
        }
    }

    /**
     * Recalculate exportStatus for an existing portal image against current Timestone state.
     *
     * - Multiple Timestone images: primary → pending (0), non-primary → NULL (skip export)
     * - Single Timestone image: always pending (0) unless already downloaded/synced with a file
     * - Former non-primary left as NULL after siblings were deleted → reset to 0
     */
    private function resolvePeopleImageExportStatus($existingImage, $dynamicExportStatus)
    {
        // Still a non-primary among multiple Timestone matches — do not export
        if ($dynamicExportStatus === null) {
            return null;
        }

        $hasFile = filled($existingImage->name) && filled($existingImage->image_path);
        $current = $existingImage->exportStatus;

        // Already through the pipeline with a file on disk — leave alone
        if ($hasFile && in_array((int) $current, [1, 2], true)) {
            return (int) $current;
        }

        // NULL leftover, missing file, failed download/upload, or pending → ensure exportable
        return 0;
    }

    public function peopleImageCount($tsJobId)
    {
        // Timestone
        $tsHomedSubjectImages = $this->timestoneTableService->getAllTimestoneHomeSubjectsImageByJobID($tsJobId);
        $tsHomedSubjectImagesCount = $tsHomedSubjectImages->count(); // Format subjects by SubjectId
        $tsHomedSubjectIds = $tsHomedSubjectImages->pluck('Subjects.SubjectID')->toArray();

        // $tsAttachedSubjectImagesCount = $this->timestoneTableService->getAllTimestoneAttachedSubjectsImageByJobID($tsJobId)->whereNotIn('SubjectFolders.SubjectID', $tsHomedSubjectIds)->groupBy('SubjectFolders.SubjectID')->count();
        // $totalTSSubjectImages = $tsHomedSubjectImagesCount + $tsAttachedSubjectImagesCount;
        $totalTSSubjectImages = $tsHomedSubjectImagesCount;

        // Blueprint
        $bpSubjectCount = $this->subjectService->getSubjectByJobId($tsJobId)->count();
        $bpHomedSubjectImages = $this->subjectService->getAllHomedSubjectsImageByJobId($tsJobId);
        $bpHomedSubjectImagesCount = $bpHomedSubjectImages->count();
        // $bpHomedSubjectIds =  $bpHomedSubjectImages->pluck('ts_subject_id')->toArray();

        // $bpFolders = $this->folderService->getFolderByJobId($tsJobId)->pluck('ts_folder_id');
        // $bpAttachedSubjectIDs = $this->folderSubjectService->getAllBlueprintSubjectFoldersByFolderIds($bpFolders->toArray())->pluck('ts_subject_id')->toArray();
        // $bpAttachedSubjectImages = $this->folderSubjectService->getAllAttachedSubjectsImageBySubjectIds($bpAttachedSubjectIDs)->whereNotIn('ts_subject_id', $bpHomedSubjectIds)->groupBy('ts_subject_id')->count();

        // $totalBPSubjectImages = $bpHomedSubjectImagesCount + $bpAttachedSubjectImages;
        $totalBPSubjectImages = $bpHomedSubjectImagesCount;

        return [
            'totalTSSubjectImages' => $totalTSSubjectImages,
            'totalBPSubjectImages' => $totalBPSubjectImages,
            'bpSubjectCount' => $bpSubjectCount
        ];
    }
}


