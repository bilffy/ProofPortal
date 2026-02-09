<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Session;
use App\Models\User;
use Livewire\Component;
use Auth;
use App\Http\Resources\UserResource;
use App\Models\School;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Crypt; 

class SchoolView extends Component
{
    public $school;
    
    public function mount($hashedId)
    {
        Session::forget([
            'selectedJob',
            'selectedSeason',
            'openJob',
            'approvedSubjectChangesCount',
            'awaitApprovalSubjectChangesCount',
        ]);//CODE BY IT
        $this->checkUserRole();
        $id = Crypt::decryptString($hashedId);//CODE BYIT
        $this->school = School::findOrFail($id);//CODE BYIT
        // $id = Hashids::decodeHex($hashedId);
        // $this->school = School::findOrFail($hashedId);//CODE BY chromedia
        
        // redirect to the manage users page if the user is a franchise level
        if (Auth::user()->isFranchiseLevel()) {
            // store a new session to set a reference of school context 
            Session::put('school_context-sid', $this->school->id);
            return redirect()->route('photography.configure-new');
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
