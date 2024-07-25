<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserInviteMail;
use Illuminate\View\View;
use Inertia\Inertia;

class InviteController extends Controller
{
    /**
     * Invite a single user.
     */
    public function inviteSingleUser(string $id)
    {
        // Retrieve the user by ID
        $user = User::findOrFail($id);
        
        // Generate the invite link (example logic, adjust as needed)
        $token = \Str::random(40); // Ensure you have a way to associate this token with the user
        $inviteLink = url("/invite/{$token}");
        
        // Send the email
        Mail::to($user->email)->send(new UserInviteMail($user, $inviteLink));

        // Return a JSON response to indicate success
        return response()->json([
            'message' => 'Invite sent successfully.',
            'inviteLink' => $inviteLink
        ]);
    }
}
