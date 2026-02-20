@props(['collapsed'=>true])

@php 
    use App\Helpers\RoleHelper;
    $visibility = $UiSettingHelper->getUiSetting($UiSettingHelper::UI_SETTING_NAV_COLLAPSED) ? 'hidden' : '';
    $subNav = false;
    $nonSchoolLevelRoles = [
        RoleHelper::ROLE_SUPER_ADMIN,
        RoleHelper::ROLE_ADMIN,
        RoleHelper::ROLE_FRANCHISE
    ];
    $user = Auth::user();
    
    $proofingMenu = $AppSettingsHelper::getByPropertyKey('proofing_menu');
    $proofingMenuValue = $proofingMenu ? $proofingMenu->property_value === 'true' ? true : false : true; 
@endphp
@hasanyrole(implode("|", $nonSchoolLevelRoles))
    @php
        if ($visibility != 'hidden' && $SchoolContextHelper->isSchoolContext()) {
            $subNav = true;
        }
    @endphp
@endhasanyrole
<div id="mainNav" class="flex flex-col mt-2 mr-2">
    <div class="py-4 px-2 flex justify-center ">
        <div id="logo" class="{{ $visibility ? "logoSM" : "logoRegular" }}"></div>
    </div>
    
    {{--Hide for now as per ticket MSP-161, this only apply for school user--}}
    @if (!$user->isSchoolLevel())
        <x-layout.navItem visibility="{{ $visibility }}" id="tabHome" navIcon="home" href="{{ route('dashboard') }}">Home</x-layout.navItem>
    @endif

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PHOTOGRAPHY))
        @unlessrole($RoleHelper::ROLE_FRANCHISE)
            <x-layout.navItem visibility="{{ $visibility }}" id="tabPhotography" navIcon="camera" href="{{ route('photography') }}">Photography</x-layout.navItem>
        @endunlessrole
    @endcan

    @hasanyrole(implode("|", $nonSchoolLevelRoles))
        @if ($SchoolContextHelper->isSchoolContext())
            <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabPhotography" navIcon="camera" href="{{ route('photography') }}">Photography</x-layout.navItem>
            
            @if ($proofingMenuValue)
                <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabProofing" navIcon="th" href="{{ route('proofing') }}">Proofing</x-layout.navItem>
            @endif
        @endif
    @endhasanyrole
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PROOFING))
        @unlessrole($RoleHelper::ROLE_FRANCHISE)
            {{--Proofing are not yet implemented, hide for now until the blueprint implemented into the system--}}
            <x-layout.navItem visibility="{{ $visibility }}" id="tabProofing" navIcon="th" href="{{ route('proofing') }}">Proofing</x-layout.navItem>
        @endunlessrole
    @endcan

    {{-- @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ORDERING))
        <x-layout.navItem visibility="{{ $visibility }}" id="tabOrder" navIcon="credit-card" href="{{ route('order') }}">Ordering</x-layout.navItem>
    @endcan --}}
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ADMIN_TOOLS))
        <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabManageUsers" navIcon="user" href="{{ route('users') }}">Manage Users</x-layout.navItem>
        @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_REPORTS))
            {{-- Reports are not yet implemented, hide for now until the blueprint implemented into the system--}}
            {{--<x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabReports" navIcon="list-ul" href="{{ route('dashboard') }}">Reports</x-layout.navItem>--}}
        @endcan
    @endcan

    @if ($user->isAdmin())
        <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabManageSettings" navIcon="cogs" href="{{ route('settings.main') }}">App Settings</x-layout.navItem>
    @endif

</div>

@push('scripts')
<script type="module">
    import { NAV_TABS } from "{{ Vite::asset('resources/js/helpers/constants.helper.ts') }}"
    import { getCurrentNav, getNavTabId } from "{{ Vite::asset('resources/js/helpers/utils.helper.ts') }}"

    // Retrieve navCollapsed state from localStorage or default to false
    let navCollapsed = "{{ $visibility == 'hidden' ? true : false }}"
    
    window.addEventListener("load", function () {
        const targetElement = `#${getNavTabId(getCurrentNav())}`;

        $(targetElement).addClass('bg-primary text-white rounded-e-md');
    }, false);
</script>

@endpush

<style>
    .logoRegular, .logoSM {
        background-repeat: no-repeat;
    }
    .logoRegular {
        width: 125px;
        min-height: 100px;
        transition: all .1s;
        background-image: url( {{ Vite::asset('resources/assets/images/MSP-Logo.svg') }} );
    }
</style>