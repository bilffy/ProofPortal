<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Session;
use App\Models\User;
use Livewire\Component;
use Auth;
use App\Http\Resources\UserResource;
use App\Models\School;
use Vinkla\Hashids\Facades\Hashids;

class SchoolView extends Component
{
    public $school;
    
    public function mount($hashedId)
    {
        $this->checkUserRole();
        // $id = Hashids::decodeHex($hashedId);
        $this->school = School::findOrFail($hashedId);
        
        // redirect to the manage users page if the user is a franchise level
        if (Auth::user()->isFranchiseLevel()) {
            // store a new session to set a reference of school context 
            Session::put('school_context-sid', $this->school->id);
            return redirect()->route('photography.configure');
        }
            
    }

    public function checkUserRole()
    {
        /** @var User $user */
        $user = Auth::user();

        // Check if the user is neither a franchise level nor a school level
        if (!$user->isFranchiseLevel() && !$user->isSchoolLevel()) {
            return redirect()->route('dashboard');
        }
    }

    public function render()
    {   
        /** @var User $user */
        $user = Auth::user();
        
        return view('livewire.school-view',
            [
                'school' => $this->school,
                'user' => $user
                
            ])
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}
