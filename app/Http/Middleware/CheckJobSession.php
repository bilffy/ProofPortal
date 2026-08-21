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
        if ($request->route('hash')) {
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

            if ($selectedJob) {
                $isAssigned = $selectedJob->jobUsers()->where('user_id', $user->id)->exists();
                if (!$isAssigned || $selectedJob->ts_account_id !== $user->getFranchise()->ts_account_id ) {
                        abort(403, 'Unauthorized access to this job.');
                }
            } else {
                abort(403, 'Unauthorized access to this job.');
            }

            // 3. Session Synchronization Check
            $currentSessionJob = Session::get('selectedJob');

            // Check if the session is empty or points to a different job
            if (!$currentSessionJob || $currentSessionJob->ts_job_id !== $selectedJob->ts_job_id) {
                
                // SECURITY: Only allow the session to switch if the URL is signed 
                // OR if there was no job in the session at all.
                if ($request->hasValidSignature() || !$currentSessionJob) {
                    $this->storeSlimJobSession($selectedJob);
                } else {
                    // Conflict detected with an unsigned URL
                    return redirect()->route('proofing')->with('error', 'A different job is currently open.');
                }
            } else {
                // Rewrite slim payload so older fat sessions (folders/subjects) do not persist
                $this->storeSlimJobSession($selectedJob);
            }

            return $next($request);
        }

        return $next($request);
    }

    private function storeSlimJobSession(Job $selectedJob): void
    {
        $selectedJob->unsetRelations();
        session(['selectedJob' => $selectedJob]);

        $season = $selectedJob->seasons()->first();
        if ($season) {
            session(['selectedSeason' => $season]);
        }
    }
}
