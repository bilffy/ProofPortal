<?php

namespace App\Http\Controllers;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\Franchise;
use App\Models\FranchiseUser;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\User;
use App\Models\UserRole;
use Auth;
use DB;
use Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 20);
        $usersQuery = User::query();
        // TODO: Add initial filter for list of users only visible to this user's permission level
        $this->applySearch($usersQuery, $request->input('search', ''));
        $this->applySort($usersQuery, $request->input('sort', ''));
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
            'roles' => RoleResource::collection(Role::getAllowedRoles($user->getRole())),
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
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'role' => 'required|integer',
        ],
        [
            'email.email' => 'Invalid format.',
            'email.unique' => 'This email address is already used by another account.'
        ]);

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
                case Role::ROLE_FRANCHISE:
                    FranchiseUser::create([
                        'user_id' => $user->id,
                        'franchise_id' => $request->franchise
                    ]);
                    break;
                case Role::ROLE_SCHOOL_ADMIN:
                case Role::ROLE_PHOTO_COORDINATOR:
                case Role::ROLE_TEACHER:
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

    protected function applySearch($query, $search)
    {
        return $query->when($search, function ($q, $searchString) {
            $q->where('email', 'like', "%{$searchString}%")
                ->orWhere('firstname', 'like', "%{$searchString}%")
                ->orWhere('lastname', 'like', "%{$searchString}%")
                // ->orWhere('name', 'like', "%{$searchString}%")
            ;
        });
    }

    protected function applyFilter($query, $filters)
    {
        // foreach($filters as $filter => $values) {
        // }
        return $query;
    }
    
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
