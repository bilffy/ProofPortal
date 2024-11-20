<?php

namespace App\Http\Livewire;

use App\Helpers\SchoolContextHelper;
use App\Models\User;
use App\Services\CollectionQueryService;
use Auth;
use Livewire\Component;
use Livewire\WithPagination;

class UsersList extends Component
{
    use WithPagination;

    public $page = 1;
    public $search = '';
    public $sortBy = 'lastname';
    public $sortDirection = 'asc';
    public $role = null;
    public $status = null;
    public $selectedRoles = [];
    public $selectedFilters = [
        'roles' => [],
        'organizations' => [],
        'status' => []
    ];
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'lastname'],
        'sortDirection' => ['except' => 'asc'],
        'role' => ['except' => [null, 'all']],
        'status' => ['except' => [null, 'all']],
        'page' => ['except' => 1],
    ];
    protected $listeners = ['filterAdded', 'filterRemoved'];

    public function sortColumn($column)
    {
        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

    public function filter()
    {
        if (!empty($this->selectedFilters['roles'])) {
            $this->filterByRoles();
        }

        if (!empty($this->selectedFilters['status'])) {
            $this->filterByStatus();
        }

        if (!empty($this->selectedFilters['organizations'])) {
            $this->filterByOrganizations();
        }
    }

    public function filterAdded($role, $type)
    {
        if (!in_array($role, $this->selectedFilters[$type])) {
            $this->selectedFilters[$type] = $role;
        }
        $this->filter();
    }

    public function filterRemoved($role, $type)
    {
        $this->selectedFilters = array_filter($this->selectedFilters[$type], fn($r) => $r !== $role);
        $this->filter();
    }

    public function filterByRoles()
    {
        $this->users = User::whereIn('role', $this->selectedFilters['roles'])->get();
    }

    public function filterByStatus()
    {
        $this->users = User::whereIn('status', $this->selectedFilters['status'])->get();
    }

    public function filterByOrganizations()
    {
        $this->users = User::whereIn('role', $this->selectedFilters['organizations'])->get();
    }

    public function search($term)
    {
        $this->search = $term;
    }

    /**
     * Lifecycle hook: Reset pagination when query params change
     */
    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'status', 'role'])) {
            $this->resetPage(); // Reset to page 1 when search changes
        }
    }

    public function updatedPage()
    {
        // $this->emit('paginationUpdated', 'data');  // Emit the event when pagination updates
    }

    private function getSortColumns($sortColumn)
    {
        switch($sortColumn) {
            case 'organization':
                return ["franchises.name", "schools.name"];
            case 'role':
                return "roles.name";
            default:
                return "users." . $sortColumn;
        }
    }

    private function getFilterColumns($filters)
    {
        $parsedFilters = [];
        foreach ($filters as $key => $values) {
            switch($key) {
                case 'organizations':
                    $parsedFilters['franchises.name'] = $values;
                    $parsedFilters['schools.name'] = $values;
                    break;
                case 'roles':
                    $parsedFilters['roles.name'] = $values;
                    break;
                case 'status':
                    $parsedFilters['users.status'] = $values;
                    break;
                default:
                    break;
            }
        }

        return $parsedFilters;
    }
    
    public function render()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isFranchiseLevel()) {
            $franchise = $user->getFranchise();
            $franchiseId = $franchise->id;
            // Check if it has school context
            // This override the franchise level context from the selected school
            $schools = SchoolContextHelper::isSchoolContext()
                ? [SchoolContextHelper::getCurrentSchoolContext()->id]
                : $franchise->schools()->get()->pluck('id');
            $usersQuery = User::whereHas('schools', function ($sQuery) use ($schools) {
                $sQuery->whereIn('schools.id', $schools);
            })->orWhereHas('franchises', function ($fQuery) use ($franchiseId) {
                $fQuery->where('franchises.id', $franchiseId);
            });
        } elseif($user->isSchoolLevel()) {
            $usersQuery = $user->getSchool()->users();
        } else {
            $usersQuery = User::query();
        }
        $usersQuery = $usersQuery
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\Models\User');
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('school_users', 'users.id', '=', 'school_users.user_id')
            ->leftJoin('schools', 'school_users.school_id', '=', 'schools.id')
            ->leftJoin('franchise_users', 'users.id', '=', 'franchise_users.user_id')
            ->leftJoin('franchises', 'franchise_users.franchise_id', '=', 'franchises.id')
            ->select('users.id', 'users.email', 'users.firstname', 'users.lastname', 'users.status');
        $queryService = new CollectionQueryService($usersQuery);
        $users = $queryService
            // ->filter($this->getFilterColumns($this->f))
            ->search(['users.email', 'users.firstname', 'users.lastname', 'schools.name', 'franchises.name'], $this->search)
            ->sort($this->getSortColumns($this->sortBy), $this->sortDirection)
            ->paginate();
        return view('livewire.users-list', compact('users'));
    }
}
