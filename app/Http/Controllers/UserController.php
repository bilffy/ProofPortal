<?php

namespace App\Http\Controllers;
use App\Helpers\ActivityLogHelper;
use App\Helpers\Constants\LogConstants;
use App\Helpers\EncryptionHelper;
use App\Helpers\SchoolContextHelper;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\Franchise;
use App\Models\FranchiseUser;
use App\Helpers\RoleHelper;
use App\Models\School;
use App\Models\Status;
use App\Models\SchoolUser;
use App\Models\User;
use App\Rules\MspEmailValidation;
use App\Services\UserService;
use Auth;
use DB;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserController extends Controller
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

    protected function validator(array $data)
    {
        $schema1 = 
            [
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'max:255',
                    'email:rfc', // Basic check for email format
                    'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // Ensures a dot + valid TLD
                    'unique:'.User::class,
                    // new MspEmailValidation(), //Disable for now
                ],
                'role' => 'required|integer',
            ];
        $schema2 = 
            [
                'firstname' => 'First Name is required.',
                'lastname' => 'Last Name is required.',
                'email.email' => 'Invalid format.',
                'email.unique' => config('app.dialog_config.account_exist.message')
            ];
        

        if (array_key_exists('role', $data)) {
            if ($data['role'] == 3 && $data['franchise'] == 0) {
                $schema1 = array_merge($schema1, ['franchise' => 'required|integer']);
                $schema2 = array_merge($schema2, ['franchise.required' => 'Franchise is required.']);
            }
            
            if ($data['role'] > 3 && $data['school'] == 0) {
                $schema1 = array_merge($schema1, ['school' => 'required|integer']);
                $schema2 = array_merge($schema2, ['school.required' => 'School is required.']);
            }
        } else {
            unset($schema1['role']);
            unset($schema1['email']);
        }
        
        $validate = Validator::make($data, $schema1, $schema2);
        
        return $validate;
    }

    public function index(Request $request)
    {
        return view('manageUsers', [
            'user' => new UserResource(Auth::user()),
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

        // Check if it has school context
        // This override the franchise level context from the selected school
        if (SchoolContextHelper::isSchoolContext()) {
            $schoolContext = SchoolContextHelper::getCurrentSchoolContext();
            $schoolList = School::orderBy('name')->where('id', '=', $schoolContext->id)->get();
        }
        
        $nonce = Str::random(40);
        session(['register_token' => $nonce]);
        
        return view('newUser', [
            'user' => new UserResource($user),
            'roles' => RoleResource::collection(RoleHelper::getAllowedRoles($user->getRole())),
            'franchises' => $franchiseList,
            'schools' => $schoolList,
            'nonce' => $nonce
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->nonce !== session('register_token')) {
            return response()->json('Invalid Request', 422);
        }

        $request->merge(
            [
                'email' => EncryptionHelper::simpleDecrypt($request->email),
                'franchise' => EncryptionHelper::simpleDecrypt($request->franchise),
                'school' => EncryptionHelper::simpleDecrypt($request->school)
            ]
        );
        
        $validator = $this->validator($request->all());
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        try {

            $status = Status::where('status_external_name', 'new')->first();
            
            $user = User::create([
                'name' => $request->firstname . " " . $request->lastname,
                'email' => $request->email,
                'username' => $request->email,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'status' => User::STATUS_NEW, // initialize user with NEW status
                'password' => Hash::make(str()->random(7)), // random value for user creation
                'active_status_id' => $status->id,
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
            // Log CREATE_USER activity
            ActivityLogHelper::log(LogConstants::CREATE_USER, ['created_user' => $user->id]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
        
        // Send invitation email
        $this->userService->sendInvite($user, auth()->user()->id);
        session()->forget('register_token'); // Remove token after use
        return response()->json(['redirect_url' => route('users')], 200);
    }

    public function edit(Request $request)
    {
        $user = Auth::user();
        $franchiseList = $user->isAdmin() ? Franchise::orderBy('name')->get()
            : ( $user->isFranchiseLevel() ? Franchise::orderBy('name')->where('id', '=', $user->getFranchise()->id)->get() : [] );
        $schoolList = $user->isAdmin() ? School::orderBy('name')->get()
            : ( $user->isFranchiseLevel()
                ? School::orderBy('name')->with('franchises')->whereHas('franchises', function ($q) use ($user) { $q->where('franchise_id', $user->getFranchise()->id); })->get()
                : School::orderBy('name')->where('id', '=', $user->getSchool()->id)->get() );

        // Check if it has school context
        // This override the franchise level context from the selected school
        if (SchoolContextHelper::isSchoolContext()) {
            $schoolContext = SchoolContextHelper::getCurrentSchoolContext();
            $schoolList = School::orderBy('name')->where('id', '=', $schoolContext->id)->get();
        }

        $nonce = Str::random(40);
        session(['edit_user_token' => $nonce]);

        $data = [
                'user' => new UserResource($user),
                'roles' => RoleResource::collection(RoleHelper::getAllowedRoles($user->getRole())),
                'franchises' => $franchiseList,
                'schools' => $schoolList,
                'nonce' => $nonce
        ];

        // if ($request->wantsJson()) {
            return response()->json($data, 200);
        // }
        // return view('editUser', $data);
    }

    /**
     * Handle user details update.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->nonce !== session('edit_user_token')) {
            return response()->json('Invalid Request', 422);
        }

        $user = Auth::user();

        $request->merge(
            [
                'email' => EncryptionHelper::simpleDecrypt($request->email),
                // 'franchise' => EncryptionHelper::simpleDecrypt($request->franchise),
                // 'school' => EncryptionHelper::simpleDecrypt($request->school)
            ]
        );

        $validator = $this->validator($request->all(['firstname', 'lastname', 'email']));
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user->update([
                'name' => $request->firstname . " " . $request->lastname,
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
            ]);
            // Log UPDATE_USER activity
            ActivityLogHelper::log(LogConstants::EDIT_USER, ['edited_user' => $user->id]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            // if ($request->wantsJson()) {
                return response()->json(['errors' => $e->getMessage()], 422);
            // }
            // return redirect()->back()->withInput()->withErrors($e->getMessage());
        }

        session()->forget('edit_user_token'); // Remove token after use
        // if ($request->wantsJson()) {
            return response()->json(['message' => 'Profile successfully updated.'], 200);
        // }
        // return response()->json(['redirect_url' => route('users')], 200);
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
