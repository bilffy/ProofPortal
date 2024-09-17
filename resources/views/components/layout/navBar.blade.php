<div id="navbarWrapper" class="flex flex-col w-[210px] mt-2">
    <div>
        <img src="{{ Vite::asset('resources/assets/images/MSP-Logo_400x400.png') }}" alt=""/>
    </div>
    <x-layout.navItem id="tabHome" navIcon="home" href="{{ route('dashboard') }}">Home</x-layout.navItem>
    <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">PHOTOGRAPHY</span>
    <x-layout.navItem id="tabPortraits" navIcon="user" href="{{ route('dashboard') }}">Portraits</x-layout.navItem>
    <x-layout.navItem id="tabGroups" navIcon="users" href="{{ route('dashboard') }}">Group</x-layout.navItem>
    <x-layout.navItem id="tabSpecialEvents" navIcon="graduation-cap" href="{{ route('dashboard') }}">Special Events</x-layout.navItem>
    <x-layout.navItem id="tabPromoPhotos" navIcon="camera" href="{{ route('dashboard') }}">Promo Photos</x-layout.navItem>
    <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">PROOFING</span>
    <x-layout.navItem id="tabProofing" navIcon="th" href="{{ route('proofing') }}">Proofing</x-layout.navItem>
    <span class="text-sm text-neutral-600 ml-4 font-bold mt-4">ADMIN TOOLS</span>
    <x-layout.navItem id="tabManageUsers" navIcon="user-plus" href="{{ route('users') }}">Manage Users</x-layout.navItem>
    <x-layout.navItem id="tabReports" navIcon="list-ul" href="{{ route('dashboard') }}">Reports</x-layout.navItem>
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
