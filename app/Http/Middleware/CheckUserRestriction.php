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
        if ($user && $user->getRole() === RoleHelper::ROLE_FRANCHISE) {
            $schoolContext = SchoolContextHelper::getCurrentSchoolContext();
            // Redirect or abort if the user doesn't meet the condition
            if (is_null($schoolContext)) {
                return redirect('/')->with('error', 'You have not selected a School.');
            }

            if ($request->is('proofing/reports*') || $request->is('proofing/invitations*')) {
                $selectedJob = Session::get('selectedJob');
                if ($selectedJob) {
                    $isAssigned = $selectedJob->jobUsers->contains('user_id', $user->id);
                    if (!$isAssigned || $selectedJob->ts_account_id !== $user->getFranchise()->ts_account_id ) {
                        abort(403, 'Unauthorized access to this job.');
                    }
                } else {
                        abort(403, 'Unauthorized access to this job.');
                }
            }
        }

        return $next($request);
    }
}
