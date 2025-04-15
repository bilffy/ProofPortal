<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Models\User;
use Auth;
use App\Helpers\SchoolContextHelper;
use Vinkla\Hashids\Facades\Hashids;

class DisableUserController extends Controller
{
    public function disable(string $id)
    {
        $id = Hashids::decodeHex($id);
        
        // Retrieve the user by ID
        /** @var User $user */
        $user = User::findOrFail($id);

        $user->status = User::STATUS_DISABLED;
        $user->disabled = true;
        $user->save();
        
        ActivityLogHelper::log(LogConstants::DISABLE_USER, ['disabled_user' => $user->id]);

       return redirect()->route('users')->with('success', "User {$user->email} has been disabled successfully");
    }
}
