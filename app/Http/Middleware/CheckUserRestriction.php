<?php

namespace App\Http\Middleware;

use App\Helpers\RoleHelper;
use App\Helpers\SchoolContextHelper;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

class CheckUserRestriction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // dd($request);
        if ($user && !$user->isAdmin()) {
            $selectedJob = Session::get('selectedJob');
            
            // SECURITY: If a job is open in the session, ensure the current user still has access to it.
            // This prevents issues where one user's open job context is carried over to an impersonated user,
            // or where a job remains open after access has been revoked.
            if ($selectedJob) {
                $isAssigned = $user->jobs()->where('jobs.ts_job_id', $selectedJob->ts_job_id)->exists();

                // Franchise users have access to any job within their franchise ts_account_id
                if (!$isAssigned && $user->isFranchiseLevel()) {
                    $franchise = $user->getFranchise();
                    if ($franchise && $selectedJob->ts_account_id === $franchise->ts_account_id) {
                        $isAssigned = true;
                    }
                }

                if (!$isAssigned) {
                    Session::forget([
                        'job-season-flag',
                        'selectedJob',
                        'selectedSeason',
                        'openJob',
                        'selectedSeasonDashboard',
                        'openSeason',
                        'approvedSubjectChangesCount',
                        'awaitApprovalSubjectChangesCount'
                    ]);
                    
                    // If the user was trying to access a specific job-related page, redirect them with an error
                    if ($request->is('proofing/*') || $request->is('franchise/*') || $request->is('subjects/*')) {
                        return redirect()->route('proofing')->with('error', 'Unauthorized access to this job.');
                    }
                }
            }

            // Specific check for invitation management routes (legacy logic maintained but improved)
            if ($request->is('proofing/invitations*') && $user->isFranchiseLevel()) {
                if (!$selectedJob) {
                    abort(403, 'Unauthorized access to this job.');
                }
                
                $franchise = $user->getFranchise();
                if ($selectedJob->ts_account_id !== $franchise->ts_account_id) {
                    abort(403, 'Unauthorized access to this job.');
                }
            }
        }

        return $next($request);
    }
}
