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
    public function inviteSingleUser(string $id)
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
}
