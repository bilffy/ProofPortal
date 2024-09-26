<?php

namespace App\Http\Controllers;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\Franchise;
use App\Models\FranchiseUser;
use App\Helpers\RoleHelper;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use App\Rules\MspEmailValidation;
use Auth;
use DB;
use Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected function validator(array $data)
    {
        return Validator::make($data,
        [
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'max:255',
                    'email:rfc,dns', // Basic check for email format and domain (dns)
                    'unique:'.User::class,
                    // new MspEmailValidation(), //Disable for now
                ],
                'role' => 'required|integer',
            ],
            [
                'firstname' => 'First Name is required.',
                'lastname' => 'Last Name is required.',
                'email.email' => 'Invalid format.',
                'email.unique' => 'This email address is already used by another account.'
            ]
        );
    }

    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 20);
        /** @var User $user */
        $user = Auth::user();
        // $filters = [];
        if ($user->isFranchiseLevel()) {
            $franchise = $user->getFranchise();
            $franchiseId = $franchise->id;
            $schools = $franchise->schools()->get()->pluck('id');
            // $filters['franchise'][] = $franchise->id;
            // $filters['school'] = $schools;
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
        // $this->applyFilter($usersQuery, $filters);
        $this->applySearch($usersQuery, $request->input('search', ''));
        $this->applySort($usersQuery, $request->input('sort', 'lastname'));
        return view('manageUsers', [
            'user' => new UserResource(Auth::user()),
            'results' => UserResource::collection($usersQuery->paginate($perPage)->withQueryString())
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $franchiseList = $user->isAdmin() ? Franchise::orderBy('name')->get()
            : ( $user->isFranchiseLevel() ? Franchise::orderBy('name')->where('id', '=', $user->getFranchise()->id)->get() : [] );
        $schoolList = $user->isAdmin() ? School::orderBy('name')->get()
            : ( $user->isFranchiseLevel()
                ? School::orderBy('name')->with('franchises')->whereHas('franchises', function ($q) use ($user) { $q->where('franchise_id', $user->getFranchise()->id); })->get()
                : School::orderBy('name')->where('id', '=', $user->getSchool()->id)->get() );
        return view('newUser', [
            'user' => new UserResource($user),
            'roles' => RoleResource::collection(RoleHelper::getAllowedRoles($user->getRole())),
            'franchises' => $franchiseList,
            'schools' => $schoolList,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = $this->validator($request->all());
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->firstname . " " . $request->lastname,
                'email' => $request->email,
                'username' => $request->email,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'status' => User::STATUS_NEW, // initialize user with NEW status
                'password' => Hash::make(str()->random(7)), // random value for user creation
            ]);
    
            $role = Role::findOrFail($request->role)->name;
            $user->assignRole($role);
    
            switch ($role)
            {
                case RoleHelper::ROLE_FRANCHISE:
                    FranchiseUser::create([
                        'user_id' => $user->id,
                        'franchise_id' => $request->franchise
                    ]);
                    break;
                case RoleHelper::ROLE_SCHOOL_ADMIN:
                case RoleHelper::ROLE_PHOTO_COORDINATOR:
                case RoleHelper::ROLE_TEACHER:
                    SchoolUser::create([
                        'user_id' => $user->id,
                        'school_id' => $request->school
                    ]);
                    break;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }

        return redirect(route('users', absolute: false));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySearch($query, $search)
    {
        return $query->when($search, function ($q, $searchString) {
            $q->where('email', 'like', "%{$searchString}%")
                ->orWhere('firstname', 'like', "%{$searchString}%")
                ->orWhere('lastname', 'like', "%{$searchString}%")
            ;
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilter($query, $filters)
    {
        foreach($filters as $filter => $values) {
            switch($filter) {
                case 'franchise':
                    break;
                case 'school':
                    break;
            }
        }
        return $query;
    }
    
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySort($query, $value)
    {
        if (!empty($value)) {
            $order = 'asc';
            if (str_starts_with($value, '-')) {
                $value = substr($value, 1);
                $order = 'desc';
            }
            $query->orderBy($value, $order);
        }
        return $query;        
    }
}
