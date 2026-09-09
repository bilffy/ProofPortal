<?php

namespace App\Http\Controllers\Proofing;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

class FranchiseSchoolEntryController extends Controller
{
    /**
     * Set school context and open that school's photography configure page.
     */
    public function __invoke(string $hashedId): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user || !$user->hasRole(RoleHelper::ROLE_FRANCHISE)) {
            return redirect()->route('dashboard');
        }

        $schoolId = (int) Crypt::decryptString($hashedId);
        School::findOrFail($schoolId);

        Session::forget([
            'selectedJob',
            'selectedSeason',
            'openJob',
            'approvedSubjectChangesCount',
            'awaitApprovalSubjectChangesCount',
        ]);
        Session::put('school_context-sid', $schoolId);

        return redirect()->route('photography.configure-new');
    }
}
