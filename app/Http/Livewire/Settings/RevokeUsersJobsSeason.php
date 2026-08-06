<?php

namespace App\Http\Livewire\Settings;

use App\Models\Folder;
use App\Models\FolderUser;
use App\Models\Job;
use App\Models\JobUser;
use App\Models\Season;
use App\Services\Proofing\StatusService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RevokeUsersJobsSeason extends Component
{
    public $selectedTsSeasonId = '';

    public $statusMessage = '';

    public $statusType = '';

    public function revokeUsersForSeason(StatusService $statusService)
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

        // Admin settings action is season-wide — skip BelongsToFranchise
        // (that scope also filters ts_schoolkey from school context, which is
        // usually empty on /settings and would return zero jobs).
        $tsJobIds = Job::withoutGlobalScope('franchise_school_access')
            ->where([
                'ts_season_id' => $season->ts_season_id,
                'show_proofing' => 1,
            ])
            ->pluck('ts_job_id')
            ->filter()
            ->values()
            ->all();

        if ($tsJobIds === []) {
            $this->statusType = 'error';
            $this->statusMessage = "No jobs found for season {$season->code}.";
            return;
        }

        $tsFolderIds = Folder::withoutGlobalScope('franchise_school_access')
            ->whereIn('ts_job_id', $tsJobIds)
            ->pluck('ts_folder_id')
            ->filter()
            ->values()
            ->all();

        $jobUsersDeleted = 0;
        $folderUsersDeleted = 0;

        DB::transaction(function () use ($tsJobIds, $tsFolderIds, &$jobUsersDeleted, &$folderUsersDeleted, $statusService) {
            if ($tsFolderIds !== []) {
                $folderUsersDeleted = FolderUser::whereIn('ts_folder_id', $tsFolderIds)->delete();
            }

            $jobUsersDeleted = JobUser::whereIn('ts_job_id', $tsJobIds)->delete();
            Job::withoutGlobalScope('franchise_school_access')
                ->whereIn('ts_job_id', $tsJobIds)
                ->update([
                    'show_proofing' => 0,
                    'job_status_id' => $statusService->none,
                    'proof_start'   => null,
                    'proof_warning' => null,
                    'proof_due'     => null,
                    'proof_catchup' => null,
                    'notifications_enabled' => null,
                    'notifications_matrix' => null,
                ]);
        });

        $this->statusType = 'success';
        $this->statusMessage = "Revoked all user access from proofing jobs for season {$season->code}.";
        $this->selectedTsSeasonId = '';
        $this->dispatch('revoke-users-season-reset');
    }

    public function render()
    {
        return view('livewire.settings.revoke-users-jobs-season', [
            'seasons' => Season::orderBy('code', 'desc')->get(),
        ]);
    }
}
