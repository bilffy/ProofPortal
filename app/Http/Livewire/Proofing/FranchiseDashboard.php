<?php

namespace App\Http\Livewire\Proofing;

use App\Helpers\RoleHelper;
use App\Helpers\SchoolContextHelper;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Proofing\FranchiseDashboardService;
use App\Services\Proofing\SeasonService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FranchiseDashboard extends Component
{
    /** Empty string = all portal + proofing seasons. */
    public string $selectedSeasonId = '';

    /** Proofing Jobs table hides Archived / Deleted jobs; each toggle (Completed / Archived / Deleted) shows only the jobs in that status. */
    public bool $showArchived = false;

    public bool $showDeleted = false;

    public bool $showCompleted = false;

    public function mount(): mixed
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasRole(RoleHelper::ROLE_FRANCHISE)) {
            return redirect()->route('dashboard');
        }

        if (SchoolContextHelper::isSchoolContext()) {
            return redirect()->route('photography.configure-new');
        }

        $season = request()->query('season', '');
        $this->selectedSeasonId = ($season === null || $season === '')
            ? ''
            : (string) $season;

        return null;
    }

    /** Show only Completed jobs (switches off the other views). */
    public function toggleCompleted(): void
    {
        $this->showCompleted = !$this->showCompleted;
        $this->showArchived = false;
        $this->showDeleted = false;
    }

    /** Show only Archived jobs (switches off the other views). */
    public function toggleArchived(): void
    {
        $this->showArchived = !$this->showArchived;
        $this->showCompleted = false;
        $this->showDeleted = false;
    }

    /** Show only Deleted jobs (switches off the other views). */
    public function toggleDeleted(): void
    {
        $this->showDeleted = !$this->showDeleted;
        $this->showCompleted = false;
        $this->showArchived = false;
    }

    public function render(FranchiseDashboardService $dashboardService, SeasonService $seasonService)
    {
        /** @var User $user */
        $user = Auth::user();

        $seasons = $seasonService
            ->getAllSeasonDataForPortalAndProofing('code', 'ts_season_id', 'is_default')
            ->orderBy('code', 'desc')
            ->get()
            ->unique('ts_season_id')
            ->values();

        $seasonFilter = $this->selectedSeasonId !== '' ? $this->selectedSeasonId : null;

        return view('livewire.proofing.franchise-dashboard', [
            'seasons' => $seasons,
            'selectedSeasonId' => $this->selectedSeasonId,
            // Heading: the name of the franchise the user belongs to.
            'franchiseName' => $user->getFranchise()?->name,
            'showArchived' => $this->showArchived,
            'showDeleted' => $this->showDeleted,
            'showCompleted' => $this->showCompleted,
            'metrics' => $dashboardService->getDashboardMetrics($user, $seasonFilter),
            'proofingJobsTable' => $dashboardService->getProofingJobsTable($user, $seasonFilter, $this->showArchived, $this->showDeleted, $this->showCompleted),
        ])->layout('layouts.authenticated', [
            'user' => new UserResource($user),
        ]);
    }
}
