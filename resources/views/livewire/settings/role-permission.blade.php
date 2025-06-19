<div class="w-full overflow-x-auto">
    <div class="overflow-hidden min-w-max">
        <h5 class="text-xl font-bold dark:text-white">User Role Permissions</h5>
        <p class="pt-3">Configure permissions for each User Role.</p>
        <p>
            <label for="roles" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white">Role</label>
            <select wire:change="showRolesDownstream($event.target.value)"  id="roles" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block p-2.5 focus:ring-0">
                <option selected>Choose a role</option>
                @foreach ($allRoles as $role)
                    <option <@if($currentRole->name == $role->name) selected @endif  value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </p>
        <div class="w-full flex flex-row gap-4 mt-4 rounded-[4px] border-[1px]">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead class="border-b-1 border-neutral-300">
                    <tr>
                        <x-table.headerCell id="header-permission-role" class="p-0.5 border-b-[1px]" clickable="{{false}}">Manage Users</x-table.headerCell>
                        <x-table.headerCell id="header-permission-create" class="p-0.5 border-b-[1px]" clickable="{{false}}" centered>Create/Invite</x-table.headerCell>
                        <x-table.headerCell id="header-permission-edit" class="p-0.5 border-b-[1px]" clickable="{{false}}" centered>Edit</x-table.headerCell>
                        <x-table.headerCell id="header-permission-disable" class="p-0.5 border-b-[1px]" clickable="{{false}}" centered>Disable</x-table.headerCell>
                        <x-table.headerCell id="header-permission-impersonate" class="p-0.5 border-b-[1px]" clickable="{{false}}" centered>Impersonate</x-table.headerCell>
                        <x-table.headerCell id="header-permission-revoke" class="p-0.5 border-b-[1px]" clickable="{{false}}" centered>Revoke</x-table.headerCell>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <x-table.cell class="w-1/6 border-none">
                                <span class="text-lg">{{ $role->name }}</span>
                            </x-table.cell>
                            <x-table.cell class="w-1/6 border-none">
                                <div class="flex justify-center">
                                    <input <@if($role->permissions->contains('name', 'invite ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                                    id="checkbox_invite_{{ $role->name }}"
                                    type="checkbox"
                                    value="{{ $role->id }}"
                                    wire:click="updatePermission('invite', '{{ $role->name }}', $event.target.checked)"
                                    class="w-4 h-4 text-primary rounded-sm focus:ring-0"/>
                                </div>
                            </x-table.cell>
                            <x-table.cell class="w-1/6 border-none">
                                <div class="flex justify-center">
                                    <input <@if($role->permissions->contains('name', 'edit ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                                    id="checkbox_edit_{{ $role->name }}"
                                    type="checkbox"
                                    value="{{ $role->id }}"
                                    wire:click="updatePermission('edit', '{{ $role->name }}', $event.target.checked)"
                                    class="w-4 h-4 text-primary rounded-sm focus:ring-0">
                                </div>
                            </x-table.cell>
                            <x-table.cell class="w-1/6 border-none">
                                <div class="flex justify-center">
                                    <input <@if($role->permissions->contains('name', 'disable ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif 
                                    id="checkbox_disable_{{ $role->name }}"
                                    type="checkbox"
                                    value="{{ $role->id }}"
                                    wire:click="updatePermission('disable', '{{ $role->name }}', $event.target.checked)"
                                    class="w-4 h-4 text-primary rounded-sm focus:ring-0">
                                </div>
                            </x-table.cell>
                            <x-table.cell class="w-1/6 border-none">
                                <div class="flex justify-center">
                                    <input <@if($role->permissions->contains('name', 'impersonate ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                                    id="checkbox_impersonate_{{ $role->name }}"
                                    type="checkbox"
                                    value="{{ $role->id }}"
                                    wire:click="updatePermission('impersonate', '{{ $role->name }}', $event.target.checked)"
                                    class="w-4 h-4 text-primary rounded-sm focus:ring-0">
                                </div>
                            </x-table.cell>
                            <x-table.cell class="w-1/6 border-none">
                                <div class="flex justify-center">
                                    <input  <@if($role->permissions->contains('name', 'revoke ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                                    id="checkbox_revoke_{{ $role->name }}"
                                    type="checkbox"
                                    value="{{ $role->id }}"
                                    wire:click="updatePermission('revoke', '{{ $role->name }}', $event.target.checked)"
                                    class="w-4 h-4 text-primary rounded-sm focus:ring-0">
                                </div>
                            </x-table.cell>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- <div class="grid grid-cols-6 p-4 text-sm font-medium text-gray-900 bg-gray-100 border-t border-b border-gray-200 gap-x-16 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            <div class="flex items-center">Role</div>
            <div>Create/Invite</div>
            <div>Edit</div>
            <div>Disable</div>
            <div>Impersonate</div>
            <div>Revoke</div>
        </div>
        <div class="grid grid-cols-6 px-4 py-5 text-sm text-gray-700 border-b border-gray-200 gap-x-16 dark:border-gray-700">
            @foreach ($roles as $role)
                <div class="text-gray-500 dark:text-gray-400">{{ $role->name }}</div>
                <div class="flex items-center">
                    <input <@if($role->permissions->contains('name', 'invite ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                            id="checkbox_invite_{{ $role->name }}"
                            type="checkbox"
                            value="{{ $role->id }}"
                            wire:click="updatePermission('invite', '{{ $role->name }}', $event.target.checked)"
                            class="w-4 h-4 text-blue-600"/>
                    
                </div>
                <div class="flex items-center">
                    <input <@if($role->permissions->contains('name', 'edit ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                           id="checkbox_edit_{{ $role->name }}"
                           type="checkbox"
                           value="{{ $role->id }}"
                           wire:click="updatePermission('edit', '{{ $role->name }}', $event.target.checked)"
                           class="w-4 h-4 text-blue-600">

                </div>
                <div class="flex items-center">
                    <input <@if($role->permissions->contains('name', 'disable ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif 
                            id="checkbox_disable_{{ $role->name }}"
                            type="checkbox"
                            value="{{ $role->id }}"
                            wire:click="updatePermission('disable', '{{ $role->name }}', $event.target.checked)"
                            class="w-4 h-4 text-blue-600">
                    
                </div>

                <div class="flex items-center">
                    <input <@if($role->permissions->contains('name', 'impersonate ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                            id="checkbox_impersonate_{{ $role->name }}"
                            type="checkbox"
                            value="{{ $role->id }}"
                            wire:click="updatePermission('impersonate', '{{ $role->name }}', $event.target.checked)"
                            class="w-4 h-4 text-blue-600">
                    
                </div>

                <div class="flex items-center">
                    <input  <@if($role->permissions->contains('name', 'revoke ' . strtolower(str_replace(' ', '_', $role->name)))) checked @endif
                            id="checkbox_revoke_{{ $role->name }}"
                            type="checkbox"
                            value="{{ $role->id }}"
                            wire:click="updatePermission('revoke', '{{ $role->name }}', $event.target.checked)"
                            class="w-4 h-4 text-blue-600">
                    
                </div>
            @endforeach    
        </div> --}}
    </div>
</div>

@push('scripts')
<script type="module">

    function selectRole(event) {
        const selectedRole = $('#roles').val();
        Livewire.dispatch('EV_UPDATE_SELECTED_ROLE', {role: selectedRole});
    };

    function initSelect2() {
        $('#roles').select2({
            minimumResultsForSearch: Infinity,
            placeholder: "Choose a role",
        });
        $('#roles').change(selectRole);
        $('#roles.select2-selection').addClass('border-neutral');
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSelect2();
        Livewire.on('EV_ROLE_LIST_UPDATED', (data) => {
            debounce(() => {
                initSelect2();
            }, 100)();
        });
    });
</script>
@endpush
