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
            'metrics' => $dashboardService->getDashboardMetrics($user, $seasonFilter),
            'proofingJobsTable' => $dashboardService->getProofingJobsTable($user, $seasonFilter),
        ])->layout('layouts.authenticated', [
            'user' => new UserResource($user),
        ]);
    }
}
