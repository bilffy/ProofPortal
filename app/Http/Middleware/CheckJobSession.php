<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Session, Crypt};
use App\Models\{Job, Folder, Subject};

class CheckJobSession
{
    public function handle(Request $request, Closure $next)
    {
        $hash = $request->route('hash');
        $user = Auth::user();

        if (!$hash) {
            return $next($request);
        }

        try {
            $decryptedKey = Crypt::decryptString($hash);
        } catch (\Exception $e) {
            abort(404, 'Invalid access token.');
        }

        // 1. Resolve Job (Checks Job -> Folder -> Subject)
        $selectedJob = Job::where('ts_jobkey', $decryptedKey)->first();

        if (!$selectedJob) {
            $folder = Folder::where('ts_folderkey', $decryptedKey)->first();
            if ($folder) {
                $selectedJob = $folder->job;
            }
        }

        if (!$selectedJob) {
            $subject = Subject::where('ts_subjectkey', $decryptedKey)->first();
            if ($subject) {
                $selectedJob = $subject->job;
            }
        }

        // 2. Final existence and Authorization Check
        if (!$selectedJob || $selectedJob->ts_account_id !== $user->getFranchise()->ts_account_id) {
            abort(403, 'Unauthorized access to this job.');
        }

        // 3. Session Synchronization Check
        $currentSessionJob = Session::get('selectedJob');

        // Check if the session is empty or points to a different job
        if (!$currentSessionJob || $currentSessionJob->ts_job_id !== $selectedJob->ts_job_id) {
            
            // SECURITY: Only allow the session to switch if the URL is signed 
            // OR if there was no job in the session at all.
            if ($request->hasValidSignature() || !$currentSessionJob) {
                session(['selectedJob' => $selectedJob]);
                
                // Keep the Season in sync (Important for your FolderService)
                $season = $selectedJob->seasons()->first();
                if ($season) {
                    session(['selectedSeason' => $season]);
                }
            } else {
                // Conflict detected with an unsigned URL
                return redirect()->route('proofing')->with('error', 'A different job is currently open.');
            }
        }

        return $next($request);
    }
}