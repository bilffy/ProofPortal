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

        $this->userService->sendInvite($user);

        return redirect()->route('users')->with('success', config('app.dialog_config.invite.sent.message') ." ". $user->email);
    }

    public function checkUserStatus(int $id)
    {
        // Retrieve the user by ID
        /** @var User $user */
        $user = User::findOrFail($id);

        if ($user->status === User::STATUS_ACTIVE) {
            return response()->json([
                'status' => User::STATUS_ACTIVE,
                'message' => 'This user is already active. Click OK to refresh the page to see the updated status.'
            ]);
        }

        return response()->json([
            'status' => $user->status,
            'message' => ''
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
            $this->userService->sendInvite($user);
        }

        // Return a JSON response to indicate success
        return response()->json([
            'message' => config('app.dialog_config.invite.sent.message') ." " . count($users) . ' users.'
        ]);
    }
}
