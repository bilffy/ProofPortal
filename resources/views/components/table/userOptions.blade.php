@props(['dropDownId', 'role', 'userId', 'userEmail', 'status', 'inviteRoute'])

<div id="options_{{$userId}}">
    <x-button.link id="btn_{{$dropDownId}}" data-dropdown-toggle={{$dropDownId}} data-initialized="false">
        <x-icon class="px-2 cursor-pointer" icon="ellipsis" />
    </x-button.link>
    <!-- Dropdown menu -->
    <x-form.dropdownPanel id={{$dropDownId}}>
        @can ($PermissionHelper->toPermission($PermissionHelper::ACT_INVITE, $role))
            @if ($userId != auth()->id())
                <li>
                    <x-button.dropdownLink
                        href="#" 
                        data-invite-route="{{ $inviteRoute }}"
                        data-modal-target="inviteModal" 
                        data-modal-toggle="inviteModal" 
                        data-user-id="{{ $userId }}" 
                        class="hover:bg-primary hover:text-white">
                        {{ $status == $User::STATUS_INVITED ? 'Re-invite' : 'Invite' }}
                    </x-button.dropdownLink>
                </li>
            @endif
        @endcan
        <li>
            <x-button.dropdownLink href="#" class="hover:bg-primary hover:text-white">Edit</x-button.dropdownLink>
        </li>
    </x-form.dropdownPanel>
</div>
