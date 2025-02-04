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
    <x-layout.navItem visibility="{{ $visibility }}" id="tabHome" navIcon="home" href="{{ route('dashboard') }}">Home</x-layout.navItem>

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PHOTOGRAPHY))
        @unlessrole($RoleHelper::ROLE_FRANCHISE)
            <x-layout.navItem visibility="{{ $visibility }}" id="tabPhotography" navIcon="camera" href="{{ route('photography') }}">Photography</x-layout.navItem>
        @endunlessrole
    @endcan

    @hasanyrole(implode("|", $nonSchoolLevelRoles))
        @if ($SchoolContextHelper->isSchoolContext())
            <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabPhotography" navIcon="camera" href="{{ route('photography') }}">Photography</x-layout.navItem>
            {{-- <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabSchoolConfig" navIcon="gear" href="{{ route('config-school') }}">Config School</x-layout.navItem> --}}
            <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabProofing" navIcon="th" href="{{ route('proofing') }}">Proofing</x-layout.navItem>
        @endif
    @endhasanyrole
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PROOFING))
        @unlessrole($RoleHelper::ROLE_FRANCHISE)
            <x-layout.navItem visibility="{{ $visibility }}" id="tabProofing" navIcon="th" href="{{ route('proofing') }}">Proofing</x-layout.navItem>
        @endunlessrole
    @endcan

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ORDERING))
        <x-layout.navItem visibility="{{ $visibility }}" id="tabProofing" navIcon="credit-card" href="{{ route('order') }}">Ordering</x-layout.navItem>
    @endcan
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ADMIN_TOOLS))
        <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabManageUsers" navIcon="user" href="{{ route('users') }}">Manage Users</x-layout.navItem>
        @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_REPORTS))
            <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabReports" navIcon="list-ul" href="{{ route('dashboard') }}">Reports</x-layout.navItem>
        @endcan
    @endcan
    <x-layout.navItem visibility="{{ $visibility }}" id="tabSchoolHome" navIcon="home" href="{{ route('test2') }}">For Testing</x-layout.navItem>
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
        
        // Collapsible Sidebar Navigation
        // $('#btnToggleNavBar').on("click", function() {
        //     navCollapsed = !navCollapsed;
        //     localStorage.setItem('navCollapsed', JSON.stringify(navCollapsed));

        //     // Animate toggle button icon
        //     $('#logo').toggleClass('logoRegular logoSM');
        //     $('#btnCollapse').toggleClass('fa-chevron-left fa-chevron-right');
        //     $('#btnCollapseExpand').toggleClass('pr-0 pr-5')
        //     $('div.hideOnCollapse').toggleClass('textCollapsed textExpanded')

        //     // AJAX call to save collapsed state
        //     $.ajax({
        //         url: '{{ route("navbar.toggleCollapse") }}',
        //         method: 'POST',
        //         data: {
        //             _token: '{{ csrf_token() }}',
        //             collapsed: navCollapsed
        //         },
        //         success: function(response) {
        //             // Optionally handle the response
        //         }
        //     });
        // });
        
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
    /* .logoSM {
        width: 47px;
        min-height: 40px;
        transition: all .3s;
        background-image: url( {{Vite::asset('resources/assets/images/MSP-Logo-sm.svg')}} );
    }

    .textCollapsed {
        width: 0 !important;
        overflow: hidden;
    }
    .textExpanded {
        width: 100%;
        overflow: hidden;
        transition: .2s all;
        padding-right: 3rem;
    } */
</style>