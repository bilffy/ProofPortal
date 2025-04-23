<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserInviteMail;
use Illuminate\View\View;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

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
    public function inviteSingleUser(string $id)
    {
        $id = Hashids::decodeHex($id);
        
        // Retrieve the user by ID
        /** @var User $user */
        $user = User::findOrFail($id);
        $user->status = User::STATUS_INVITED;
        $user->save();
        
        $this->userService->sendInvite($user, auth()->user()->id);

        return redirect()->route('users')->with('success', config('app.dialog_config.invite.sent.message') ." ". $user->email);
    }

    public function checkUserStatus(string $id)
    {

        $id = Hashids::decodeHex($id);
        
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
        $senderId = auth()->user()->id;
        // Send invitations to each user
        foreach ($users as $user) {
            $this->userService->sendInvite($user, $senderId);
        }

        // Return a JSON response to indicate success
        return response()->json([
            'message' => config('app.dialog_config.invite.sent.message') ." " . count($users) . ' users.'
        ]);
    }
}
