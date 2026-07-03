<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Auth;

class SettingsController extends Controller
{
    public function main(Request $request)
    {   
        $user = Auth::user();
        
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard');
        }
        
        return view('settings', [
            'user' => new UserResource($user),
            
        ]);
    }

    public function syncSeasons(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $baseUrl = rtrim(config('services.bpsync.url'), '/');
        
        try {
            $response = \Illuminate\Support\Facades\Http::withOptions([
                'verify' => config('services.bpsync.verify_ssl', false)
            ])->timeout(120)->get("{$baseUrl}/seasons/sync");

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'success']);
            }

            return response()->json(['success' => false, 'message' => 'Failed: ' . $response->status()], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function syncFranchises(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $baseUrl = rtrim(config('services.bpsync.url'), '/');
        
        try {
            $response = \Illuminate\Support\Facades\Http::withOptions([
                'verify' => config('services.bpsync.verify_ssl', false)
            ])->timeout(120)->get("{$baseUrl}/franchises/sync");

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'success']);
            }

            return response()->json(['success' => false, 'message' => 'Failed: ' . $response->status()], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function syncSchools(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $baseUrl = rtrim(config('services.bpsync.url'), '/');
        
        try {
            $response = \Illuminate\Support\Facades\Http::withOptions([
                'verify' => config('services.bpsync.verify_ssl', false)
            ])->timeout(180)->get("{$baseUrl}/schools/sync?full=1");

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'success']);
            }

            return response()->json(['success' => false, 'message' => 'Failed: ' . $response->status()], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}