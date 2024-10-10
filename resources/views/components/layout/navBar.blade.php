@props(['collapsed'=>true])

<div class="flex flex-col mt-2 mr-2">
    <div class="py-4 px-2 flex justify-center">
        <img 
        src="{{ Vite::asset('resources/assets/images/MSP-Logo.svg') }}" 
        alt=""
        width=125px
        class="hideOnCollapse"
        />
        <img 
        src="{{ Vite::asset('resources/assets/images/MSP-Logo-sm.svg') }}" 
        alt=""
        width=47px
        class="showOnCollapse"
        />
    </div>
    <div class="relative h-[40px] flex justify-end">
        <span id="btnToggleNavBar" class="absolute bg-neutral p-2 hover:bg-primary-hover hover:cursor-pointer hover:transition -right-6 w-[32px] h-[32px] flex justify-center items-center rounded-full">
            <x-icon id="btnCollapse" icon="arrow-left" style="color: #ffffff"/>
        </span>
    </div>
    <x-layout.navItem id="tabHome" navIcon="home" href="{{ route('dashboard') }}">Home</x-layout.navItem>

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PHOTOGRAPHY))
        <span class="hideOnCollapse text-sm text-neutral-600 ml-4 font-bold mt-4">PHOTOGRAPHY</span>
        <x-layout.navItem id="tabPortraits" navIcon="user" href="{{ route('dashboard') }}">Portraits</x-layout.navItem>
        <x-layout.navItem id="tabGroups" navIcon="users" href="{{ route('dashboard') }}">Groups</x-layout.navItem>
        <x-layout.navItem id="tabSpecialEvents" navIcon="graduation-cap" href="{{ route('dashboard') }}">Special Events</x-layout.navItem>
        <x-layout.navItem id="tabPromoPhotos" navIcon="camera" href="{{ route('dashboard') }}">Promo Photos</x-layout.navItem>
    @endcan
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PROOFING))
        <span class="hideOnCollapse text-sm text-neutral-600 ml-4 font-bold mt-4">PROOFING</span>
        <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('proofing') }}">Proofing</x-layout.navItem>
    @endcan

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ORDERING))
        <span class="hideOnCollapse text-sm text-neutral-600 ml-4 font-bold mt-4">ORDERING</span>
        <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('dashboard') }}">ID Cards</x-layout.navItem>
        <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('dashboard') }}">Photos</x-layout.navItem>
        <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('dashboard') }}">Yearbooks</x-layout.navItem>
    @endcan
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ADMIN_TOOLS))
        <span class="hideOnCollapse text-sm text-neutral-600 ml-4 font-bold mt-4">ADMIN TOOLS</span>
        <x-layout.navItem id="tabManageUsers" navIcon="user-plus" href="{{ route('users') }}">Manage Users</x-layout.navItem>
        <x-layout.navItem id="tabReports" navIcon="list-ul" href="{{ route('dashboard') }}">Reports</x-layout.navItem>
    @endcan
    <span class="hideOnCollapse text-sm ml-4 font-bold mt-4 text-alert">TESTING PAGE</span>
    <x-layout.navItem id="tabSchoolHome" navIcon="home" href="{{ route('test2') }}">School Home</x-layout.navItem>
</div>

@push('scripts')
<script type="module">
    //let navCollapsed = false;
    import { NAV_TABS } from "{{ Vite::asset('resources/js/helpers/constants.helper.ts') }}"
    import { getCurrentNav, getNavTabId } from "{{ Vite::asset('resources/js/helpers/utils.helper.ts') }}"

    // Retrieve navCollapsed state from localStorage or default to false
    let navCollapsed = JSON.parse(localStorage.getItem('navCollapsed')) || false;
    
    window.addEventListener("load", function () {
        const targetElement = `#${getNavTabId(getCurrentNav())}`;
        
        $(targetElement).addClass('bg-primary text-white rounded-e-md');
        
        // Set initial state based on navCollapsed value
        if (navCollapsed) {
            $('span.hideOnCollapse').hide();
            $('#btnCollapse').addClass("fa-arrow-right").removeClass("fa-arrow-left");
            $('img.hideOnCollapse').hide();
            $('img.showOnCollapse').show();
        } else {
            $('#btnCollapse').addClass("fa-arrow-left").removeClass("fa-arrow-right");
            $('img.hideOnCollapse').show();
            $('img.showOnCollapse').hide();
            $('span.hideOnCollapse').show();
        }

        $('#btnToggleNavBar').on("click", function() {
            navCollapsed = !navCollapsed;
            localStorage.setItem('navCollapsed', JSON.stringify(navCollapsed));

            // Toggle icon
            $('#btnCollapse').toggleClass("fa-arrow-left fa-arrow-right");
            $('img.hideOnCollapse').animate({width: 'toggle'});
            $('img.showOnCollapse').animate({width: 'toggle'});
            $('span.hideOnCollapse').animate({width: 'toggle'});
        });
        
    }, false);
</script>

@endpush
