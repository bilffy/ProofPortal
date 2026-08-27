<?php

namespace App\Http\Middleware;

use App\Helpers\SchoolContextHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolContext
{
    /**
     * Block proofing routes for franchise users until a school is selected.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->isFranchiseLevel()) {
            return $next($request);
        }

        if (SchoolContextHelper::isSchoolContext() && SchoolContextHelper::getCurrentSchoolContext()) {
            return $next($request);
        }

        $message = 'Please select a school before accessing Proofing.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()
            ->route('school.list')
            ->with('error', $message);
    }
}
