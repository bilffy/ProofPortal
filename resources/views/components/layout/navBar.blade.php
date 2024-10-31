@props(['collapsed'=>true])

@php 
    $visibility = $UiSettingHelper->getUiSetting($UiSettingHelper::UI_SETTING_NAV_COLLAPSED) ? 'hidden' : '';
@endphp
<div class="flex flex-col mt-2 mr-2">
    <div class="py-4 px-2 flex justify-center "> {{-- min-w-[250px] --}}
        <img 
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
        />
    </div>
    <div class="relative h-[40px] flex justify-end">
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
        <x-layout.navItem visibility="{{ $visibility }}" id="tabManageUsers" navIcon="user-plus" href="{{ route('users') }}">Manage Users</x-layout.navItem>
        @can ($PermissionHelper->getAccessToPage($PermissionHelper::SUB_REPORTS))
            <x-layout.navItem visibility="{{ $visibility }}" id="tabReports" navIcon="list-ul" href="{{ route('dashboard') }}">Reports</x-layout.navItem>
        @endcan
    @endcan
    <span class="hideOnCollapse {{ $visibility }} whitespace-nowrap text-sm ml-4 font-bold mt-4 text-alert">TESTING PAGE</span>
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

        /*$('#btnToggleNavBar').on("click", function() {
            navCollapsed = !navCollapsed;
            localStorage.setItem('navCollapsed', JSON.stringify(navCollapsed));

            $('#btnCollapse').animate({}, 600, function() {
                $(this).toggleClass('fa-chevron-left fa-chevron-right');
            });

            $('span.hideOnCollapse').animate({}, 600, function() {
                $(this).toggleClass('hidden visible');
            });
            // $('span.hideOnCollapse').toggle("slide", {direction: 'left'})

            $('img.hideOnCollapse').animate({}, 600, function() {
                $(this).toggleClass('hidden visible');
            });
            // $('img.hideOnCollapse').toggleSlide()
            
            $('img.showOnCollapse').animate({}, 600, function() {
                $(this).toggleClass('visible hidden');
            });
            // $('img.showOnCollapse').toggleSlide()

            $.ajax({
                url: '{{ route("navbar.toggleCollapse") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    collapsed: navCollapsed
                },
                success: function(response) {}
            });    
        });*/

        $('#btnToggleNavBar').on("click", function() {
            navCollapsed = !navCollapsed;
            localStorage.setItem('navCollapsed', JSON.stringify(navCollapsed));

            // Animate toggle button icon
            $('#btnCollapse').toggleClass('fa-arrow-left fa-arrow-right');

            // Group animations for smoother transitions
            var elementsToToggle = $('span.hideOnCollapse, img.hideOnCollapse, img.showOnCollapse');

            elementsToToggle.each(function() {
                $(this).animate({width: 'toggle'}, function() {
                    $(this).toggleClass('hidden visible');
                });
            });

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
