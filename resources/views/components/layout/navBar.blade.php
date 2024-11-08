@props(['collapsed'=>true])

@php 
    $visibility = $UiSettingHelper->getUiSetting($UiSettingHelper::UI_SETTING_NAV_COLLAPSED) ? 'hidden' : '';
    $subNav = false;
    
    if ($visibility != 'hidden' && $SchoolContextHelper->isSchoolContext()) {
        $subNav = true;
    }
    
@endphp
<div id="mainNav" class="flex flex-col mt-2 mr-2">
    <div class="py-4 px-2 flex justify-center "> {{-- min-w-[250px] --}}
        <div id="logo" class="{{ $visibility ? "logoSM" : "logoRegular" }}"></div>

        {{-- <img 
        src="{{ Vite::asset('resources/assets/images/MSP-Logo.svg') }}" 
        alt=""
        width=125px
        class="hideOnCollapse {{ $visibility }}" 
        />
        <img 
        src="{{ Vite::asset('resources/assets/images/MSP-Logo-sm.svg') }}" 
        alt=""
        width=47px
        class="showOnCollapse {{ $visibility != 'hidden' ? 'hidden' : '' }}"
        /> --}}
    </div>
    <div id="btnCollapseExpand" class="relative h-[40px] flex justify-end {{ $visibility ? "pr-5" : "pr-0" }}">
        <span id="btnToggleNavBar" class="p-2 hover:cursor-pointer hover:transition -right-6 w-[32px] h-[32px] flex justify-center items-center rounded-full">
            @php $arrow =  $visibility == 'hidden' ? 'chevron-right' : 'chevron-left'  @endphp
            <x-icon id="btnCollapse" icon="{{ $arrow }}" style="color: #323232"/>    
               
        </span>
    </div>
    <x-layout.navItem visibility="{{ $visibility }}" id="tabHome" navIcon="home" href="{{ route('dashboard') }}">Home</x-layout.navItem>

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PHOTOGRAPHY))
        @unlessrole($RoleHelper::ROLE_FRANCHISE)
            {{--<span class="hideOnCollapse {{ $visibility }} whitespace-nowrap text-sm text-neutral-600 ml-4 font-bold mt-4">PHOTOGRAPHY</span>
            <x-layout.navItem visibility="{{ $visibility }}" id="tabPortraits" navIcon="user" href="{{ route('dashboard') }}">Portraits</x-layout.navItem>
            <x-layout.navItem visibility="{{ $visibility }}" id="tabGroups" navIcon="users" href="{{ route('dashboard') }}">Groups</x-layout.navItem>
            <x-layout.navItem visibility="{{ $visibility }}" id="tabSpecialEvents" navIcon="graduation-cap" href="{{ route('dashboard') }}">Special Events</x-layout.navItem>--}}
            <x-layout.navItem visibility="{{ $visibility }}" id="tabPromoPhotos" navIcon="camera" href="{{ route('dashboard') }}">Photography</x-layout.navItem>
        @endunlessrole
    @endcan
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PROOFING))
        @unlessrole($RoleHelper::ROLE_FRANCHISE)
            {{--<span class="hideOnCollapse {{ $visibility }} whitespace-nowrap text-sm text-neutral-600 ml-4 font-bold mt-4">PROOFING</span>--}}
            <x-layout.navItem visibility="{{ $visibility }}" id="tabProofing" navIcon="th" href="{{ route('proofing') }}">Proofing</x-layout.navItem>
        @endunlessrole
    @endcan

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ORDERING))
        {{--<span class="hideOnCollapse {{ $visibility }} whitespace-nowrap text-sm text-neutral-600 ml-4 font-bold mt-4">ORDERING</span>
        <x-layout.navItem visibility="{{ $visibility }}" id="tabProofing" navIcon="th" href="{{ route('dashboard') }}">ID Cards</x-layout.navItem>
        <x-layout.navItem visibility="{{ $visibility }}" id="tabProofing" navIcon="th" href="{{ route('dashboard') }}">Photos</x-layout.navItem>--}}
        <x-layout.navItem visibility="{{ $visibility }}" id="tabProofing" navIcon="credit-card" href="{{ route('dashboard') }}">Ordering</x-layout.navItem>
    @endcan
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ADMIN_TOOLS))
        {{-- <span class="hideOnCollapse {{ $visibility }} whitespace-nowrap text-sm text-neutral-600 ml-4 font-bold mt-4">ADMIN TOOLS</span> --}}
        <x-layout.navItem visibility="{{ $visibility }}"  subNav="{{ $subNav }}" id="tabManageUsers" navIcon="user" href="{{ route('users') }}">Manage Users</x-layout.navItem>
        @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_REPORTS))
            <x-layout.navItem visibility="{{ $visibility }}" subNav="{{ $subNav }}" id="tabReports" navIcon="list-ul" href="{{ route('dashboard') }}">Reports</x-layout.navItem>
        @endcan
    @endcan
    {{-- <span class="hideOnCollapse {{ $visibility }} whitespace-nowrap text-sm ml-4 font-bold mt-4 text-alert">TESTING PAGE</span> --}}
    <x-layout.navItem visibility="{{ $visibility }}" id="tabSchoolHome" navIcon="home" href="{{ route('test2') }}">School Home</x-layout.navItem>
</div>

@push('scripts')
<script type="module">
    //let navCollapsed = false;
    import { NAV_TABS } from "{{ Vite::asset('resources/js/helpers/constants.helper.ts') }}"
    import { getCurrentNav, getNavTabId } from "{{ Vite::asset('resources/js/helpers/utils.helper.ts') }}"

    // Retrieve navCollapsed state from localStorage or default to false
    let navCollapsed = "{{ $visibility == 'hidden' ? true : false }}"
    
    window.addEventListener("load", function () {
        const targetElement = `#${getNavTabId(getCurrentNav())}`;

        $(targetElement).addClass('bg-primary text-white rounded-e-md');

        $('#btnToggleNavBar').on("click", function() {
            navCollapsed = !navCollapsed;
            localStorage.setItem('navCollapsed', JSON.stringify(navCollapsed));

            // Animate toggle button icon
            $('#logo').toggleClass('logoRegular logoSM');
            $('#btnCollapse').toggleClass('fa-chevron-left fa-chevron-right');
            $('#btnCollapseExpand').toggleClass('pr-0 pr-5')
            $('div.hideOnCollapse').toggleClass('textCollapsed textExpanded')
            
            console.log(navCollapsed);
            
            var isSchoolContext = {{ $SchoolContextHelper->isSchoolContext() ? 1 : 0 }};
            
            if (!navCollapsed && isSchoolContext) {
                $("#tabManageUsers").addClass("pl-8");
                $("#tabReports").addClass("pl-8");
            } else {
                $("#tabManageUsers").removeClass("pl-8").addClass('pl-4');
                $("#tabReports").removeClass("pl-8").addClass('pl-4');
            }
            
            // AJAX call to save collapsed state
            $.ajax({
                url: '{{ route("navbar.toggleCollapse") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    collapsed: navCollapsed
                },
                success: function(response) {
                    // Optionally handle the response
                }
            });
        });
        
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
    .logoSM {
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
    }
</style>