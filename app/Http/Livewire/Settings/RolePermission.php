<?php

namespace App\Http\Livewire\Settings;

use App\Http\Resources\UserResource;
use App\Models\Setting;
use App\Models\User;
use Auth;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermission extends Component
{
    public $roles = [];
    public $currentRoleSelected = 'Admin';
    public $listeners = [
        'EV_UPDATE_SELECTED_ROLE' => 'showRolesDownstream',
    ];
    
    public function mount()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return redirect()->route('dashboard');
        }
        
        $this->showRolesDownstream($this->currentRoleSelected);
        $this->permissionStates = $this->permissions ?? [];
    }
    
    public function updatePermission($permission, $role, $value)
    {
        $permission = Permission::findByName($permission . ' ' . strtolower(str_replace(' ', '_', $role)));
        /** @var Role $role */
        $role = Role::findByName($role);
        
        if ($value === true) {
            $permission->assignRole($role);
        } else {
            
            
            
            $permission->removeRole($role);
        }
        //$permission->save();
        //$permission->syncRoles([$role]);
    }
    
    public function render()
    {   
        $role =  Role::where('name', '=', $this->currentRoleSelected)->get();
        $roles = Role::where('id', '>', 1)->get();
        
        return view('livewire.settings.role-permission',
            [
                'roles' => $this->roles,
                'currentRoleSelected' => $this->currentRoleSelected,
                'allRoles' => $roles,
                'currentRole' => $role->first(),
                
            ])
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }

    // This function shows Downstream roles based on the selected role
    public function showRolesDownstream($role) {
        if ($role == 'Super Admin') {
            $roles =  Role::all();
        } elseif ($role == 'Admin') {
            $roles =  Role::where('id', '>', 1)->get();
        } elseif ($role == 'Franchise') {
            $roles =  Role::where('id', '>', 3)->get();
        } elseif ($role == 'School Admin') {
            $roles =  Role::where('id', '>', 4)->get();
        } elseif ($role == 'Photo Coordinator') {
            $roles =  Role::where('id', '>', 4)->get();
        } else {
            $roles =  Role::where('name', $role)->get();
        }
        
        $this->roles = $roles;
        $this->currentRoleSelected = $role;

        $this->dispatch('EV_ROLE_LIST_UPDATED', []);
    }
    
}
