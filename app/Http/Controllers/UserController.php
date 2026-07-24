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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Nullix\JsAesPhp\JsAesPhp;

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

    protected function validator(array $data, $user = null)
    {
        $uniqueEmail = empty($user) ? 'unique:'.User::class : Rule::unique('users', 'email')->ignore($user->id);
        $schema1 = 
            [
                //'firstname' => 'required|string|max:255',
                //'lastname' => 'required|string|max:255',
                'firstname' => 'required|string|max:50|no_special_chars',
                'lastname' => 'required|string|max:50|no_special_chars',
                'email' => [
                    'required',
                    'string',
                    'max:255',
                    'email:rfc', // Basic check for email format
                    'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // Ensures a dot + valid TLD
                    // 'unique:'.User::class,
                    $uniqueEmail,
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

    public function searchSchools(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('q');
        $page = $request->input('page', 1);
        $offset = ($page - 1) * 30;

        $query = School::query()->orderBy('name');

        if ($user->isFranchiseLevel()) {
            $franchiseId = $user->getFranchise()->id;
            $query->whereHas('franchises', function ($q) use ($franchiseId) {
                $q->where('franchise_id', $franchiseId);
            });
        } elseif (!$user->isAdmin()) {
            $query->where('id', $user->getSchool()->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('suburb', 'like', "%{$search}%");
            });
        }

        $totalCount = $query->count();
        $schools = $query->offset($offset)->limit(30)->get(['id', 'name', 'suburb']);

        return response()->json([
            'results' => $schools->map(function ($school) {
                return [
                    'id' => $school->id,
                    'text' => $school->suburb
                        ? $school->name . ' (' . $school->suburb . ')'
                        : $school->name,
                ];
            }),
            'pagination' => [
                'more' => ($offset + 30) < $totalCount
            ]
        ]);
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
        $schoolList = $user->isAdmin() ? School::orderBy('name')->limit(10)->get()
            : ( $user->isFranchiseLevel()
                ? School::orderBy('name')->with('franchises')->whereHas('franchises', function ($q) use ($user) { $q->where('franchise_id', $user->getFranchise()->id); })->limit(10)->get()
                : School::orderBy('name')->where('id', '=', $user->getSchool()->id)->get() );

        // Check if it has school context
        // This override the franchise level context from the selected school
        if (SchoolContextHelper::isSchoolContext()) {
            $schoolContext = SchoolContextHelper::getCurrentSchoolContext();
            $schoolList = School::orderBy('name')->where('id', '=', $schoolContext->id)->get();
        }

        $nonce = Str::random(16);
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
        $encryptedData = $request->input('request');
        $nonce = session('register_token');

        try {
            $decrypted = JsAesPhp::decrypt($encryptedData, $nonce);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid Request'], 400);
        }
        
        if ($decrypted['nonce'] !== $nonce) {
            return response()->json('Invalid Request', 422);
        }
        
        $validator = $this->validator($decrypted);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $scopeError = $this->validateRegistrationScope($decrypted);
        if ($scopeError !== null) {
            return response()->json(['errors' => ['role' => [$scopeError]]], 422);
        }
        
        DB::beginTransaction();
        try {

            $status = Status::where('status_external_name', 'new')->first();
            
            $user = User::create([
                'name' => $decrypted['firstname'] . " " . $decrypted['lastname'],
                'email' => $decrypted['email'],
                'username' => $decrypted['email'],
                'firstname' => $decrypted['firstname'],
                'lastname' => $decrypted['lastname'],
                'status' => User::STATUS_NEW, // initialize user with NEW status
                'password' => Hash::make(Str::random(32)),
                'active_status_id' => $status->id,
                'email_verified_at' => now(), // Auto-verify for immediate access
            ]);
    
            $role = Role::findOrFail($decrypted['role'])->name;
            $user->assignRole($role);
    
            switch ($role)
            {
                case RoleHelper::ROLE_FRANCHISE:
                    FranchiseUser::create([
                        'user_id' => $user->id,
                        'franchise_id' => $decrypted['franchise']
                    ]);
                    break;
                case RoleHelper::ROLE_SCHOOL_ADMIN:
                case RoleHelper::ROLE_PHOTO_COORDINATOR:
                case RoleHelper::ROLE_TEACHER:
                    SchoolUser::create([
                        'user_id' => $user->id,
                        'school_id' => $decrypted['school']
                    ]);
                    break;
            }
            // Log CREATE_USER activity
            ActivityLogHelper::log(LogConstants::CREATE_USER, ['created_user' => $user->id]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User registration failed', ['exception' => $e]);
            return redirect()->back()->withInput()->withErrors(['general' => 'Unable to create user. Please try again.']);
        }
        
        // Send invitation email
        $this->userService->sendInvite($user, auth()->user()->id);
        session()->forget('register_token'); // Remove token after use
        return response()->json(['redirect_url' => route('users')], 200);
    }

    public function edit(Request $request)
    {
        $isApiRequest = $request->ajax() || $request->expectsJson();
        $editTokenName = $isApiRequest ? 'edit_profile_token' : 'edit_user_token';
        $user = Auth::user();
        if (!$isApiRequest) {
            $editUserId = $request->route('id');
            if ($user->canEdit($editUserId)) {
                $editUser = User::findOrFail($editUserId);
            } else {
                // redirect to users list with error
                return redirect()->route('users')->with('error', 'Unable to process your request');
            }
        }

        $nonce = Str::random(40);
        session([$editTokenName => $nonce]);

        if ($isApiRequest) {
            $data = [
                'user' => new UserResource($user),
                'nonce' => $nonce
            ];
            return response()->json($data, 200);
        }

        $franchiseList = $user->isAdmin() ? Franchise::orderBy('name')->get()
            : ( $user->isFranchiseLevel() ? Franchise::orderBy('name')->where('id', '=', $user->getFranchise()->id)->get() : [] );
        $schoolList = $user->isAdmin() ? School::orderBy('name')->limit(10)->get()
            : ( $user->isFranchiseLevel()
                ? School::orderBy('name')->with('franchises')->whereHas('franchises', function ($q) use ($user) { $q->where('franchise_id', $user->getFranchise()->id); })->limit(10)->get()
                : School::orderBy('name')->where('id', '=', $user->getSchool()->id)->get() );

        // Ensure the target user's school is in the list
        if (isset($editUser) && $editUser->isSchoolLevel()) {
            $targetSchool = $editUser->getSchool();
            if ($targetSchool && !$schoolList->contains('id', $targetSchool->id)) {
                $schoolList->push($targetSchool);
            }
        }

        // Check if it has school context
        // This override the franchise level context from the selected school
        if (SchoolContextHelper::isSchoolContext()) {
            $schoolContext = SchoolContextHelper::getCurrentSchoolContext();
            $schoolList = School::orderBy('name')->where('id', '=', $schoolContext->id)->get();
        }

        $data = [
            'user' => new UserResource($user),
            'targetUser' => new UserResource($isApiRequest ? $user : $editUser),
            'roles' => RoleResource::collection(RoleHelper::getAllowedRoles($user->getRole())),
            'franchises' => $franchiseList,
            'schools' => $schoolList,
            'nonce' => $nonce
        ];

        return view('editUser', $data);
    }

    /**
     * Handle user details update.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $input = $request->json()->all();
        $isEditUserForm = $request->route('id');
        $editTokenName = $isEditUserForm ? 'edit_user_token' : 'edit_profile_token';
        $user = Auth::user();
        
        if ($isEditUserForm) {
            $editUserId = $request->route('id');
            if (Auth::user()->canEdit($editUserId)) {
                $user = User::findOrFail($editUserId);
            } else {
                // redirect to users list with error
                return redirect()->route('users')->with('error', 'Unable to process your request');
            }
        }
        
        if (!$user) {
            return response()->json('User not found', 404);
        }
        // If json() is empty, try getting the raw content
        if (empty($input)) {
            $input = json_decode($request->getContent(), true) ?? [];
        }
        // If still empty, try decrypting the 'request' input
        if (empty($input)) {
             try {
                $encryptedData = $request->input('request');
                $input = JsAesPhp::decrypt($encryptedData, session($editTokenName));
            } catch (\Throwable $e) {
                $nonce = Str::random(40);
                session([$editTokenName => $nonce]);
                return response()->json(['error' => 'Invalid Request', 'nonce' => $nonce], 400);
            }
        }
        if (empty($input['nonce']) || $input['nonce'] !== session($editTokenName)) {
            return response()->json('Invalid Request', 422);
        }

        $keys = $isEditUserForm
            ? ['firstname', 'lastname', 'email', 'role', 'school', 'franchise']
            : ['firstname', 'lastname', 'email'];
        // get key value pairs from $input based on $keys array
        $formInput = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $input)) {
                $formInput[$key] = $input[$key];
            }
        }

        $validator = $this->validator($formInput, $user);
        if ($validator->fails()) {
            $nonce = Str::random(40);
            session([$editTokenName => $nonce]);
            return response()->json(['errors' => $validator->errors(), 'nonce' => $nonce], 422);
        }

        DB::beginTransaction();
        try {
            $updatedData = $formInput;
            $updatedData['name'] = $formInput['firstname'] . " " . $formInput['lastname'];
            $originalRole = $user->getRoleId();
            $originalFranchise = $user->isFranchiseLevel() ? $user->getFranchiseId() : 0;
            $originalSchool = $user->isSchoolLevel() ? $user->getSchoolId() : 0;
            if ($isEditUserForm) {
                $user->update($updatedData);
                $changes = $user->getChanges();
                
                if ($changes && array_key_exists('email', $changes)) {
                    // If email is changed, send invitation email
                    $this->userService->sendInvite($user, auth()->user()->id);
                }
                
                $role = Role::findOrFail($updatedData['role'])->name;
                // If role is changed, update the role assignments
                // If role is the same but franchise/school is changed, update the respective assignments
                if ($user->hasRole($role)) {
                    if ($user->hasRole(RoleHelper::ROLE_FRANCHISE)) {
                        $franchiseUser = FranchiseUser::where('user_id', $user->id)->first();
                        if ($franchiseUser && $franchiseUser->franchise_id != $updatedData['franchise']) {
                            $franchiseUser->update(['franchise_id' => $updatedData['franchise']]);
                        }
                    } elseif ($user->hasAnyRole([RoleHelper::ROLE_SCHOOL_ADMIN, RoleHelper::ROLE_PHOTO_COORDINATOR, RoleHelper::ROLE_TEACHER])) {
                        $schoolUser = SchoolUser::where('user_id', $user->id)->first();
                        if ($schoolUser && $schoolUser->school_id != $updatedData['school']) {
                            $schoolUser->update(['school_id' => $updatedData['school']]);
                        }
                    }
                } else {
                    // Remove existing roles and assign new role
                    $oldRoles = $user->getRoleNames();
                    foreach ($oldRoles as $roleName) {
                        // Delete FranchiseUser or SchoolUser based on old role
                        switch ($roleName)
                        {
                            case RoleHelper::ROLE_FRANCHISE:
                                // FranchiseUser::get([
                                //     'user_id' => $user->id,
                                // ])->first()->delete(); //CODE BY chromedia
                                FranchiseUser::where('user_id', $user->id)->first()?->delete(); //CODE BY IT
                                break;
                            case RoleHelper::ROLE_SCHOOL_ADMIN:
                            case RoleHelper::ROLE_PHOTO_COORDINATOR:
                            case RoleHelper::ROLE_TEACHER:
                                // SchoolUser::get([
                                //     'user_id' => $user->id,
                                // ])->first()->delete(); //CODE BY chromedia
                                SchoolUser::where('user_id', $user->id)->first()?->delete(); //CODE BY IT
                                break;
                        }
                        $user->removeRole($roleName);
                    }
                    $user->assignRole($role);
                    // Add new role assignments
                    switch ($role)
                    {
                        case RoleHelper::ROLE_FRANCHISE:
                            FranchiseUser::create([
                                'user_id' => $user->id,
                                'franchise_id' => $updatedData['franchise']
                            ]);
                            break;
                        case RoleHelper::ROLE_SCHOOL_ADMIN:
                        case RoleHelper::ROLE_PHOTO_COORDINATOR:
                        case RoleHelper::ROLE_TEACHER:
                            SchoolUser::create([
                                'user_id' => $user->id,
                                'school_id' => $updatedData['school']
                            ]);
                            break;
                    }
                }
            } else {
                $user->update($updatedData);
                $changes = $user->getChanges();
            }
            $updatedFields = [];
            foreach ($updatedData as $field => $value) {
                switch ($field) {
                    case 'role':
                        if ($value != $originalRole) {
                            $updatedFields[$field] = $value;
                        }
                        break;
                    case 'franchise':
                        if (0 == $originalFranchise || $value != $originalFranchise) {
                            $updatedFields[$field] = $value;
                        }
                        break;
                    case 'school':
                        if (0 == $originalSchool || $value != $originalSchool) {
                            $updatedFields[$field] = $value;
                        }
                        break;
                    default:
                        if (array_key_exists($field, $changes)) {
                            $updatedFields[$field] = $value;
                        }
                }
            }
            // Log UPDATE_USER activity
            ActivityLogHelper::log(LogConstants::EDIT_USER, ['edited_user' => $user->id, 'updated_fields' => $updatedFields]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User update failed', ['exception' => $e]);
            $nonce = Str::random(40);
            session([$editTokenName => $nonce]);
            return response()->json(['errors' => ['general' => ['Unable to update user. Please try again.']], 'nonce' => $nonce], 422);
        }

        $reponseData = [];
        $message = 'User successfully updated.';
        $reponseData['message'] = $message;
        
        if ($isEditUserForm) {
            $reponseData['redirect_url'] = redirect()->route('users')->with('success', $message)->getTargetUrl();
            session()->forget($editTokenName); // Remove token after use
        } else {
            $nonce = Str::random(40);
            session([$editTokenName => $nonce]);
            $reponseData['nonce'] = $nonce;
        }
        
        return response()->json($reponseData, 200);
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

    protected function validateRegistrationScope(array $data): ?string
    {
        $creator = Auth::user();
        $allowedRoleIds = collect(RoleHelper::getAllowedRoles($creator->getRole()))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!in_array((int) $data['role'], $allowedRoleIds, true)) {
            return 'You are not allowed to assign this role.';
        }

        $role = Role::findOrFail($data['role'])->name;

        if ($role === RoleHelper::ROLE_FRANCHISE) {
            if (!$creator->isAdmin()) {
                $creatorFranchiseId = $creator->getFranchise()?->id;
                if (!$creatorFranchiseId || (int) $data['franchise'] !== (int) $creatorFranchiseId) {
                    return 'You are not allowed to assign this franchise.';
                }
            }
        }

        if (in_array($role, [
            RoleHelper::ROLE_SCHOOL_ADMIN,
            RoleHelper::ROLE_PHOTO_COORDINATOR,
            RoleHelper::ROLE_TEACHER,
        ], true)) {
            $schoolId = (int) ($data['school'] ?? 0);
            if ($creator->isAdmin()) {
                return null;
            }

            if ($creator->isFranchiseLevel()) {
                $allowed = School::where('id', $schoolId)
                    ->whereHas('franchises', fn ($q) => $q->where('franchise_id', $creator->getFranchise()->id))
                    ->exists();
                if (!$allowed) {
                    return 'You are not allowed to assign this school.';
                }
            } elseif (!$creator->isSchoolLevel() || (int) $creator->getSchool()->id !== $schoolId) {
                return 'You are not allowed to assign this school.';
            }
        }

        return null;
    }
}
