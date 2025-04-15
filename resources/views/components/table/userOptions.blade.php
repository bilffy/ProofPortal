@props(
    [
        'dropDownId', 
        'role', 
        'userId', 
        'userEmail', 
        'status', 
        'inviteRoute', 
        'impersonateRoute' => '', 
        'checkStatusRoute' => '', 
        'user' => null,
        'disableRoute' => ''
    ]
)

<div id="options_{{$userId}}">
    <x-button.link id="btn_{{$dropDownId}}" data-dropdown-toggle={{$dropDownId}} data-initialized="false">
        <x-icon class="px-2 cursor-pointer" icon="ellipsis" />
    </x-button.link>
    <!-- Dropdown menu -->
    <x-form.dropdownPanel id={{$dropDownId}}>
        @can ($PermissionHelper->toPermission($PermissionHelper::ACT_INVITE, $role))
            @if ($userId != auth()->id() && $status != $User::STATUS_ACTIVE)
                <li>
                    <x-button.dropdownLink
                        href="#" 
                        data-invite-route="{{ $inviteRoute }}"
                        data-invite-check-user-status-route="{{ $checkStatusRoute }}"
                        data-modal-target="inviteModal" 
                        data-modal-toggle="inviteModal" 
                        data-user-id="{{ $userId }}" 
                        class="hover:bg-primary hover:text-white">
                        {{ $status == $User::STATUS_INVITED ? 'Re-invite' : 'Invite' }}
                    </x-button.dropdownLink>
                </li>
            @endif
        @endcan
        
        
{{--            @canImpersonate($guard = null)--}}
                @if ($PermissionHelper::canImpersonate($userId))
                    <li>
                        <x-button.dropdownLink
                                href="#"
                                data-impersonate-route="{{ $impersonateRoute }}"
                                data-modal-target="impersonateModal"
                                data-modal-toggle="impersonateModal"
                                data-user-id="{{ $userId }}"
                                class="hover:bg-primary hover:text-white">
                            Impersonate
                        </x-button.dropdownLink>
                    </li>
               @endif
{{--            @endCanImpersonate--}}

        @if (auth()->user()->canDisable($userId))
            <li>
                <x-button.dropdownLink
                        href="#"
                        data-disable-route="{{ $disableRoute }}"
                        data-modal-target="disableModal"
                        data-modal-toggle="disableModal"
                        data-user-id="{{ $userId }}"
                        class="hover:bg-primary hover:text-white">
                    Disable
                </x-button.dropdownLink>
            </li>
        @endif
    </x-form.dropdownPanel>
</div>
