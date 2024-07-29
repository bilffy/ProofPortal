<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserInviteMail;
use Illuminate\View\View;
use Inertia\Inertia;

class InviteController extends Controller
{
    protected UserService $userService;

    /**
     * Create a new controller instance.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    
    /**
     * Invite a single user.
     */
    public function inviteSingleUser(int $id)
    {
        // Retrieve the user by ID
        /** @var User $user */
        $user = User::findOrFail($id);

        $this->userService->sendInvite($user->email, $user);

        // Return a JSON response to indicate success
        return response()->json([
            'message' => 'Invite sent successfully.'
        ]);
    }

    /**
     * Invite multiple users.
     * 
     * Example Request:
     * POST /invite
     * {
     *      "user_ids": [1, 2, 3, 4]
     * }
     */
    public function inviteMultipleUsers(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $validated['user_ids'];
        $users = User::whereIn('id', $userIds)->get();

        // Send invitations to each user
        foreach ($users as $user) {
            $this->userService->sendInvite($user->email, $user);
        }

        // Return a JSON response to indicate success
        return response()->json([
            'message' => 'Invites sent successfully to ' . count($users) . ' users.'
        ]);
    }
}
