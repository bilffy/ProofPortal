<?php

namespace App\Http\Livewire\Settings;

use App\Models\Job;
use App\Models\Season;
use App\Services\Proofing\StatusService;
use Livewire\Component;

class ArchiveJobsSeason extends Component
{
    public $selectedTsSeasonId = '';

    public $statusMessage = '';

    public $statusType = '';

    public function archiveJobsForSeason(StatusService $statusService)
    {
        $this->statusMessage = '';
        $this->statusType = '';

        $this->validate([
            'selectedTsSeasonId' => ['required', 'integer'],
        ], [
            'selectedTsSeasonId.required' => 'Please select a season.',
        ]);

        $season = Season::where('ts_season_id', $this->selectedTsSeasonId)->first();

        if (!$season) {
            $this->statusType = 'error';
            $this->statusMessage = 'Season not found.';
            return;
        }

        $archivedStatusId = $statusService->archived;

        if (!$archivedStatusId) {
            $this->statusType = 'error';
            $this->statusMessage = 'Archived status was not found in the system.';
            return;
        }

        // Season-wide admin action — skip BelongsToFranchise school/franchise scope.
        $jobsQuery = Job::withoutGlobalScope('franchise_school_access')
            ->where([
                'ts_season_id' => $season->ts_season_id,
                'show_proofing' => 1,
            ])
            ->where(function ($query) use ($archivedStatusId) {
                $query->whereNull('job_status_id')
                    ->orWhere('job_status_id', '!=', $archivedStatusId);
            });

        $jobsCount = (clone $jobsQuery)->count();

        if ($jobsCount === 0) {
            $this->statusType = 'error';
            $this->statusMessage = "No jobs to archive for season {$season->code}.";
            return;
        }

        $updated = $jobsQuery->update(['job_status_id' => $archivedStatusId]);

        $this->statusType = 'success';
        $this->statusMessage = "{$updated} jobs for season {$season->code} have been archived.";
        $this->selectedTsSeasonId = '';
        $this->dispatch('archive-jobs-season-reset');
    }

    public function render()
    {
        return view('livewire.settings.archive-jobs-season', [
            'seasons' => Season::orderBy('code', 'desc')->get(),
        ]);
    }
}
