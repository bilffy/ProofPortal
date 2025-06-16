<div class="w-full overflow-x-auto">
    <div class="overflow-hidden min-w-max">
        <h5 class="text-xl font-bold dark:text-white">User Role Permissions</h5>
        <p class="pt-3">Configure permissions for each User Role.</p>
        <p>
            <label for="roles" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Role</label>
            <select wire:change="showRolesDownstream($event.target.value)"  id="roles" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option selected>Choose a role</option>
                @foreach ($allRoles as $role)
                    <option <@if($currentRole->name == $role->name) selected @endif  value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach    
            </select>
        </p>
        <div class="grid grid-cols-5 p-4 text-sm font-medium text-gray-900 bg-gray-100 border-t border-b border-gray-200 gap-x-16 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            <div class="flex items-center">Role</div>
            <div>Create/Invite</div>
            <div>Disable</div>
            <div>Impersonate</div>
            <div>Revoke</div>
        </div>
        <div class="grid grid-cols-5 px-4 py-5 text-sm text-gray-700 border-b border-gray-200 gap-x-16 dark:border-gray-700">
            @foreach ($roles as $role)
                <div class="text-gray-500 dark:text-gray-400">{{ $role->name }}</div>
                <div class="flex items-center">
                    <input <@if($role->permissions->contains('name', 'invite ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                            id="checkbox_invite_{{ $role->name }}"
                            type="checkbox"
                            value="{{ $role->id }}"
                            wire:click="updatePermission('invite', '{{ $role->name }}', $event.target.checked)"
                            class="w-4 h-4 text-blue-600"/>
                    <label for="checkbox_invite_{{ $role->name }}" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Invite {{ $role->name }}</label>
                </div>
                <div class="flex items-center">
                    <input <@if($role->permissions->contains('name', 'disable ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif 
                            id="checkbox_disable_{{ $role->name }}"
                            type="checkbox"
                            value="{{ $role->id }}"
                            wire:click="updatePermission('disable', '{{ $role->name }}', $event.target.checked)"
                            class="w-4 h-4 text-blue-600">
                    <label for="checkbox_disable_{{ $role->name }}" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Disable {{ $role->name }}</label>
                </div>

                <div class="flex items-center">
                    <input <@if($role->permissions->contains('name', 'impersonate ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                            id="checkbox_impersonate_{{ $role->name }}"
                            type="checkbox"
                            value="{{ $role->id }}"
                            wire:click="updatePermission('impersonate', '{{ $role->name }}', $event.target.checked)"
                            class="w-4 h-4 text-blue-600">
                    <label for="checkbox_impersonate_{{ $role->name }}" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Impersonate {{ $role->name }}</label>
                </div>

                <div class="flex items-center">
                    <input  <@if($role->permissions->contains('name', 'revoke ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                            id="checkbox_revoke_{{ $role->name }}"
                            type="checkbox"
                            value="{{ $role->id }}"
                            wire:click="updatePermission('revoke', '{{ $role->name }}', $event.target.checked)"
                            class="w-4 h-4 text-blue-600">
                    <label for="checkbox_revoke_{{ $role->name }}" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Revoke {{ $role->name }}</label>
                </div>
            @endforeach    
        </div>
    </div>
</div>
