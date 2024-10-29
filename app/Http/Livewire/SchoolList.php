<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Auth;
use App\Http\Resources\UserResource;
use App\Models\School;

class SchoolList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public function mount()
    {
        $this->checkUserRole();
    }

    public function checkUserRole()
    {
        /** @var User $user */
        $user = Auth::user();

        // Check if the user is neither an admin nor a franchise
        if (!$user->isRcUser() && !$user->isFranchiseLevel()) {
            return redirect()->route('dashboard');
        }
    }


    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function performSearch()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }
    
    public function render()
    {   
        /** @var User $user */
        $user = Auth::user();
        
        $hideFranchise = $user->isFranchiseLevel() ? true : false;
        
        $schools = School::query()
            ->leftJoin('school_franchises', 'schools.id', '=', 'school_franchises.school_id')
            ->leftJoin('franchises', 'school_franchises.franchise_id', '=', 'franchises.id')
            ->select('schools.*', 'franchises.name as franchise_name')
            ->where(function($query) {
                $query->where('schools.name', 'like', '%' . $this->search . '%')
                    ->orWhere('schools.schoolkey', 'like', '%' . $this->search . '%')
                    ->orWhere('franchises.name', 'like', '%' . $this->search . '%');
            });

        if ($user->isFranchiseLevel()) {
            $franchise = $user->getFranchise();
            $schools->where('franchises.id', $franchise->id);    
        }
        
        $schools->orderBy($this->sortField === 'franchise_name' ? 'franchises.name' : 'schools.' . $this->sortField, $this->sortDirection);

        return view('livewire.school-list',
            [
                'schools' => $schools->paginate(20),
                'hideFranchise' => $hideFranchise,
            ])
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}
