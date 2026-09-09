<?php

namespace App\Http\Livewire\Proofing;

use App\Helpers\RoleHelper;
use App\Helpers\SchoolContextHelper;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Proofing\SchoolDashboardService;
use App\Services\Proofing\SeasonService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SchoolDashboard extends Component
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

        if (!SchoolContextHelper::isSchoolContext()) {
            return redirect()->route('school.list');
        }

        $season = request()->query('season', '');
        $this->selectedSeasonId = ($season === null || $season === '')
            ? ''
            : (string) $season;

        return null;
    }

    public function render(SchoolDashboardService $dashboardService, SeasonService $seasonService)
    {
        /** @var User $user */
        $user = Auth::user();
        $school = SchoolContextHelper::getCurrentSchoolContext();

        if (!$school) {
            return redirect()->route('school.list');
        }

        $seasons = $seasonService
            ->getAllSeasonDataForPortalAndProofing('code', 'ts_season_id', 'is_default')
            ->orderBy('code', 'desc')
            ->get()
            ->unique('ts_season_id')
            ->values();

        $metrics = $dashboardService->getMetrics(
            $school,
            $user,
            $this->selectedSeasonId !== '' ? $this->selectedSeasonId : null
        );

        $roleCounts = $dashboardService->getSchoolRoleUserCounts($school->id);
        $recentActivity = $dashboardService->getRecentActivity($school, $user);

        return view('livewire.proofing.school-dashboard', [
            'school' => $school,
            'seasons' => $seasons,
            'metrics' => $metrics,
            'roleCounts' => $roleCounts,
            'recentActivity' => $recentActivity,
        ])->layout('layouts.authenticated', [
            'user' => new UserResource($user),
        ]);
    }
}
