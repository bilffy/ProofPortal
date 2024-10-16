<?php

namespace App\Http\Livewire;

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
        $schools = School::query()
            ->leftJoin('school_franchises', 'schools.id', '=', 'school_franchises.school_id')
            ->leftJoin('franchises', 'school_franchises.franchise_id', '=', 'franchises.id')
            ->select('schools.*', 'franchises.name as franchise_name')
            ->where(function($query) {
                $query->where('schools.name', 'like', '%' . $this->search . '%')
                    ->orWhere('schools.schoolkey', 'like', '%' . $this->search . '%')
                    ->orWhere('franchises.name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField === 'franchise_name' ? 'franchises.name' : 'schools.' . $this->sortField, $this->sortDirection)
            ->paginate(20);
        
        return view('livewire.school-list',
            [
                'schools' => $schools,
            ])
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}
