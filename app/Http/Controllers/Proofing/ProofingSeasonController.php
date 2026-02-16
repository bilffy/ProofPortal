<?php

namespace App\Http\Controllers\Proofing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Proofing\ProofingChangelogService;
use App\Services\Proofing\EncryptDecryptService;
use App\Services\Proofing\StatusService;
use App\Services\Proofing\SeasonService;
use App\Services\Proofing\SchoolService;
use Illuminate\Support\Facades\Session;
use App\Services\Proofing\JobService;
use App\Services\Proofing\TimestoneTableService;
use App\Http\Resources\UserResource;
use App\Helpers\SchoolContextHelper;
use Auth;

class ProofingSeasonController extends Controller
{
    protected $jobService;
    protected $encryptDecryptService;
    protected $schoolService;
    protected $statusService;
    protected $seasonService;
    protected $proofingChangelogService;
    protected $timestoneTableService;

    public function __construct(JobService $jobService, SchoolService $schoolService, EncryptDecryptService $encryptDecryptService, StatusService $statusService, SeasonService $seasonService, ProofingChangelogService $proofingChangelogService, TimestoneTableService $timestoneTableService)
    {

        $this->jobService = $jobService;
        $this->encryptDecryptService = $encryptDecryptService;
        $this->schoolService = $schoolService;
        $this->statusService = $statusService;
        $this->seasonService = $seasonService;
        $this->proofingChangelogService = $proofingChangelogService;
        $this->timestoneTableService = $timestoneTableService;
    }

    private function getDecryptData($hash){
        return $this->encryptDecryptService->decryptStringMethod($hash);
    }

    public function viewSeason()
    {
        $user = Auth::user();
        $allSeasons = $this->seasonService->getAllSeasonData('ts_season_id', 'code', 'ts_season_key', 'is_default')->orderBy('id', 'desc')->get();
        return view('proofing.open-season', [
            'user' => new UserResource($user), // Passing the authenticated user
            'allSeasons' => $allSeasons
        ]);
    }

    public function passSeason(Request $request)
    {   
        // Store session data
        session([
            'openSeason' => true
        ]);
        return redirect()->route('dashboard.openSeason',['selectedSeasonId' => $request->season_key_hash]);
    }

    public function openSeason(Request $request, $selectedSeason)
    {
        $getSeason = $this->getDecryptData($selectedSeason);
        $selectedSeason = $this->seasonService->getSeasonByTimestoneSeasonId($getSeason)->first();
    
        if ($selectedSeason) {
            session([
                'selectedSeasonDashboard' => [
                    'ts_season_id' => $selectedSeason->ts_season_id,
                    'code' => $selectedSeason->code,
                ],
                'job-season-flag' => true
            ]);
        }

        $user = Auth::user();

        $tsJobs = $this->timestoneTableService->getAllTimestoneJobsBySeasonID($getSeason, $user->getFranchise()->ts_account_id, SchoolContextHelper::getCurrentSchoolContext()->schoolkey)->get();
        $bpJobs = $this->jobService->getJobsBySeason(SchoolContextHelper::getCurrentSchoolContext()->schoolkey, $getSeason)->pluck('ts_jobkey');
        $filteredTsJobs = $tsJobs->reject(function ($tsJob) use ($bpJobs) {
            return $bpJobs->contains($tsJob->JobKey);
        });
        
        return view('proofing.open-season-job', [
            'user' => new UserResource($user),
            'selectedSeason' => $selectedSeason,
            'tsJobs' => $filteredTsJobs
        ]);
    }   
    
    public function closeSeason()
    {
        // Forget only the keys we intend to remove
        session()->forget([
            'job-season-flag',
            'selectedJob',
            'openJob',
            'selectedSeason',
            'selectedSeasonDashboard',
            'openSeason'
        ]);
    
        // Force save then regenerate ID so the browser receives a fresh session cookie
        session()->save();
        session()->regenerate();
    
        return redirect()->route('proofing');
    }      
    
}
