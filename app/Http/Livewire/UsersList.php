<?php

namespace App\Http\Livewire;

use App\Helpers\RoleHelper;
use App\Helpers\SchoolContextHelper;
use App\Models\User;
use App\Services\CollectionQueryService;
use Auth;
use Livewire\Component;
use Livewire\WithPagination;

class UsersList extends Component
{
    use WithPagination;

    public $statusOptions = [
        User::STATUS_NEW => 'New',
        User::STATUS_INVITED => 'Invited',
        User::STATUS_ACTIVE => 'Active',
        User::STATUS_DISABLED => 'Disabled',
    ];
    public $roleOptions = [];
    // public $orgOptions = [];

    public $page = 1;
    public $search = '';
    public $sortBy = 'lastname';
    public $sortDirection = 'asc';
    public $role = null;
    public $status = null;
    public $filterBy = [
        'role' => [],
        // 'organization' => [],
        'status' => []
    ];
    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'lastname'],
        'sortDirection' => ['except' => 'asc'],
        'role' => ['except' => [null, 'all']],
        'status' => ['except' => [null, 'all']],
        'page' => ['except' => [1, '1']],
        'filterBy',
    ];
    protected $listeners = ['performFilter'];

    private function getRoleOptions(string $role)
    {
        $roleOptions = [];
        $allowedRoles = RoleHelper::getRoleNamesForFilter($role);
        foreach (RoleHelper::getRolesByNames($allowedRoles) as $role) {
            $key = base64_encode($role->id);
            $roleOptions[$key] = $role->name;
        }
        return $roleOptions;
    }

    // private function getOrgOptions($franchises, $schools)
    // {
    //     $options = [];
    //     foreach ($franchises as $franchise) {
    //         $key = base64_encode("F_$franchise->id");
    //         $options["franchises"][$key] = $franchise->name;
    //     }
    //     foreach ($schools as $school) {
    //         $key = base64_encode("S_$school->id");
    //         $options["schools"][$key] = $school->name;
    //     }
    //     return $options;
    // }

    public function sortColumn($column)
    {
        $this->sortDirection = $this->sortBy === $column && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortBy = $column;
    }

    public function performFilter($value, $type)
    {
        $this->filterBy[$type] = $value;
        $this->resetPage();
    }

    public function performSearch()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Lifecycle hook: Reset pagination when query params change
     */
    public function updating($name, $value)
    {
        if (in_array($name, ['search'])) {
            // $this->search = trim($this->search);
            $this->resetPage(); // Reset to page 1 when search changes
        }
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
                // case 'organization':
                //     foreach ($values as $value) {
                //         $decodedVal = explode("_", base64_decode($value));
                //         $val = intval($decodedVal[1]);
                //         switch($decodedVal[0]) {
                //             case "F":
                //                 $parsedFilters['franchises.id'][] = $val;
                //                 break;
                //             case "S":
                //                 $parsedFilters['schools.id'][] = $val;
                //                 break;
                //         }
                //     }
                //     break;
                case 'role':
                    $parsedFilters['roles.id'] = array_map(function ($value) {
                        $decodedValue = base64_decode($value);
                        return intval($decodedValue);
                    }, $values);
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

    public function mount()
    {
        /** @var User $user */
        $user = Auth::user();
        // Disable school/franchise filter feature
        // $franchises = [];
        // $schools = [];
        // if ($user->isFranchiseLevel()) {
        //     if (SchoolContextHelper::isSchoolContext()) {
        //         $schools = [SchoolContextHelper::getCurrentSchoolContext()];
        //     } else {
        //         $franchise = $user->getFranchise();
        //         $franchises = [$franchise];
        //         $schools = $franchise->schools;
        //     }
        // } elseif($user->isSchoolLevel()) {
        //     $schools = [$user->getSchool()];
        // } else {
        //     $franchises = Franchise::all();
        //     $schools = School::all();
        // }
        $this->roleOptions = $this->getRoleOptions($user->getRole());
        // $this->orgOptions = $this->getOrgOptions($franchises, $schools);
    }
    
    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $usersQuery = User::query()
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
        if ($user->isSchoolLevel() || SchoolContextHelper::isSchoolContext()) {
            $school = SchoolContextHelper::isSchoolContext() ? SchoolContextHelper::getCurrentSchoolContext() : $user->getSchool();
            $usersQuery->where('schools.id', $school->id);
        } elseif ($user->isFranchiseLevel()) {
            $franchise = $user->getFranchise();
            $schools = $franchise->schools->pluck('id');
            $usersQuery->where(function ($query) use ($schools, $franchise) {
                $query
                    ->whereIn('schools.id', $schools)
                    ->orWhere('franchises.id', $franchise->id);
            });
        }
        $queryService = new CollectionQueryService($usersQuery);
        $users = $queryService
            ->filter($this->getFilterColumns($this->filterBy))
            ->search(['users.email', 'users.firstname', 'users.lastname', 'schools.name', 'franchises.name'], trim($this->search))
            ->sort($this->getSortColumns($this->sortBy), $this->sortDirection)
            ->paginate();
        
        $configMessages = [
            'invite_user' => config('app.dialog_config.invite.user'),
            'impersonate' => config('app.dialog_config.impersonate'),
        ];
        
        return view('livewire.users-list', compact('users', 'configMessages'), [
            
        ]);
    }
}
