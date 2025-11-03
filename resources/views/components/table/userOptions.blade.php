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
        'disableRoute' => '',
        'editRoute' => '',
    ]
)

@php
    use App\Helpers\PermissionHelper;
    use App\Models\User;

    $isUser = $userId == auth()->id();
    $isNotActiveOtherUser = !$isUser && $status != User::STATUS_ACTIVE;
    $canInvite = PermissionHelper::toPermission(PermissionHelper::ACT_INVITE, subject: $role);
    $canEdit = auth()->user()->canInvite($userId);
    $canImpersonate = PermissionHelper::canImpersonate($userId);
    $canDisable = auth()->user()->canDisable($userId) && $status != User::STATUS_DISABLED;

    $disableOptions = $isUser || !($canDisable || $canImpersonate || $canEdit || ($canInvite && $isNotActiveOtherUser));
@endphp

<div id="options_{{$userId}}" @if($disableOptions) class="hidden" @endif>
    <x-button.link id="btn_{{$dropDownId}}" data-dropdown-toggle={{$dropDownId}} data-initialized="false">
        <x-icon class="px-2 cursor-pointer" icon="ellipsis" />
    </x-button.link>
    <!-- Dropdown menu -->
    <x-form.dropdownPanel id={{$dropDownId}}>
        @can ($canInvite)
            @if ($isNotActiveOtherUser)
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
        {{-- Use 'Can Invite' for 'Can Edit' decisions --}}
        @if ($canEdit)
            <li>
                <x-button.dropdownLink
                        href="{{ $editRoute }}"
                        class="hover:bg-primary hover:text-white">
                    Edit
                </x-button.dropdownLink>
            </li>
        @endif
{{--@canImpersonate($guard = null)--}}
        @if ($canImpersonate)
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
{{--@endCanImpersonate--}}
        @if ($canDisable)
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
