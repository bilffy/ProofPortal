<div id="navbarWrapper" class="flex flex-col w-[190px] min-w-[190px] mt-2">
    <div class="p-4 flex justify-center">
        <img 
        src="{{ Vite::asset('resources/assets/images/MSP-Logo.svg') }}" 
        alt=""
        width=125px
        />
    </div>
    <x-layout.navItem id="tabHome" navIcon="home" href="{{ route('dashboard') }}">Home</x-layout.navItem>

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PHOTOGRAPHY))
        <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">PHOTOGRAPHY</span>
        <x-layout.navItem id="tabPortraits" navIcon="user" href="{{ route('dashboard') }}">Portraits</x-layout.navItem>
        <x-layout.navItem id="tabGroups" navIcon="users" href="{{ route('dashboard') }}">Groups</x-layout.navItem>
        <x-layout.navItem id="tabSpecialEvents" navIcon="graduation-cap" href="{{ route('dashboard') }}">Special Events</x-layout.navItem>
        <x-layout.navItem id="tabPromoPhotos" navIcon="camera" href="{{ route('dashboard') }}">Promo Photos</x-layout.navItem>
    @endcan
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_PROOFING))
        <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">PROOFING</span>
        <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('proofing') }}">Proofing</x-layout.navItem>
    @endcan

    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ORDERING))
        <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">ORDERING</span>
        <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('dashboard') }}">ID Cards</x-layout.navItem>
        <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('dashboard') }}">Photos</x-layout.navItem>
        <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('dashboard') }}">Yearbooks</x-layout.navItem>
    @endcan
    
    @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_ADMIN_TOOLS))
        <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">ADMIN TOOLS</span>
        <x-layout.navItem id="tabManageUsers" navIcon="user-plus" href="{{ route('users') }}">Manage Users</x-layout.navItem>
        <x-layout.navItem id="tabReports" navIcon="list-ul" href="{{ route('dashboard') }}">Reports</x-layout.navItem>
    @endcan
    <span class="text-sm ml-4 font-bold mt-4 text-alert">TESTING PAGE</span>
    <x-layout.navItem id="tabSchoolHome" navIcon="home" href="{{ route('test2') }}">School Home</x-layout.navItem>
</div>

@push('scripts')
<script type="module">
    import { NAV_TABS } from "{{ Vite::asset('resources/js/helpers/constants.helper.ts') }}"
    import { getCurrentNav, getNavTabId } from "{{ Vite::asset('resources/js/helpers/utils.helper.ts') }}"
    window.addEventListener("load", function () {
        const targetElement = `#${getNavTabId(getCurrentNav())}`;
        $(targetElement).addClass('bg-primary text-white rounded-e-md');
    }, false);
</script>

@endpush
