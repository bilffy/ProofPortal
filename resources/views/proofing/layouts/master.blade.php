
@php
    $selectedJob = session('selectedJob') ?? [];
    $selectedSeason = session('selectedSeason') ?? [];
    $selectedSeasonDashboard = session('selectedSeasonDashboard') ?? [];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>
        <!-- MSP LOGO -->
        <link rel="shortcut icon" href="{{ URL::asset('proofing-assets/img/msp_logo.svg') }}">
        <!-- Fonts -->
        <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>
        <!-- Scripts and Styles -->
        @vite(entrypoints: ['resources/css/app.scss', 'resources/js/app.ts'])
        @livewireStyles
        <script type="module" src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js" integrity="sha512-4MvcHwcbqXKUHB6Lx3Zb5CEAVoE9u84qN+ZSMM6s7z8IeJriExrV3ND5zRze9mxNlABJ6k864P/Vl8m0Sd3DtQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" integrity="sha512-aD9ophpFQ61nFZP6hXYu4Q/b/USW7rpLCQLX6Bi0WJHXNO7Js/fUENpBQf/+P4NtpzNX0jSgR5zVvPOJp+W2Kg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <!-- Icons -->
        {{-- <link href="{{ asset('proofing-assets/vendors/css/font-awesome.min.css') }}" rel="stylesheet"> --}}
        <link href="{{ asset('proofing-assets/css/montserrat_font_css.css') }}" rel="stylesheet">
        <link href="{{ asset('proofing-assets/vendors/css/simple-line-icons.min.css') }}" rel="stylesheet">
        <link href="{{ asset('proofing-assets/css/style.css') }}" rel="stylesheet">
        <link href="{{ asset('proofing-assets/custom/custom.css') }}" rel="stylesheet">
        <link href="{{ asset('proofing-assets/custom/table-columns.css') }}" rel="stylesheet"> 

        @yield('css')
        <script>
            var base_url = "{{URL::to('/')}}";

            function debounce(func, delay) {
                let timer;
                return function (...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => {
                        func.apply(this, args);
                    }, delay);
                };
            }

            function isEmptyString(obj) {
                return typeof obj === 'string' && obj.trim() === '';
            }

            function escapeHtml(str) {
                return String(str || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            window.addEventListener('show-toast-message', e => {
                const {status, message} = e.detail;
                const safeMessage = escapeHtml(message);
                const id = `toast-${status}-${Math.random().toString(36).substr(2, 9)}` ;
                const color = status === 'success' ? 'green' : 'red';
                const iconPath = status === 'success' 
                    ? `<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>`
                    : `<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.536 13.536a1 1 0 0 1-1.414 0L10 11.914l-2.122 2.122a1 1 0 0 1-1.414-1.414L8.586 10 6.464 7.879a1 1 0 0 1 1.414-1.414L10 8.586l2.122-2.122a1 1 0 1 1 1.414 1.414L11.414 10l2.122 2.122a1 1 0 0 1 0 1.414Z"/>`;
                const wrapper = document.getElementById(`toast-wrapper`);
                if (!wrapper) return;
                const html = 
                    `<div id="${id}" class="z-[100] flex items-center w-full max-w-xs p-4 mb-4 text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800 fixed top-5 right-5" role="alert">
                        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-${color}-500 bg-${color}-100 rounded-lg dark:bg-${color}-800 dark:text-${color}-200">
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                ${iconPath}
                            </svg>
                            <span class="sr-only">${status === 'success' ? 'Check' : 'Error'} icon</span>
                        </div>
                        <div class="ms-3 text-sm font-normal">${safeMessage}</div>
                    </div>`;
                wrapper.insertAdjacentHTML('beforeend', html);
                const toastEl = document.getElementById(id);
                if (toastEl) {
                    $(toastEl).hide().fadeIn(150).delay(4000).fadeOut(300, function () {
                        this.remove();
                    });
                }
            });
        </script>
    </head>

    <body class="font-sans antialiased">
        <div id="toast-wrapper"></div>
        {{-- @if(session('success'))
            <x-toast-success message="{!! session('success') !!}" />
        @elseif(session('error'))
            <x-toast-error message="{!!  session('error') !!}" />
        @endif --}}

        <div class="flex flex-row">
            <x-layout.navBar />
            <div class="flex flex-col w-full h-screen">
                <header class="w-full flex justify-between pl-4 pr-4 mr-2 py-2 min-h-[68px] ">
                    <div class="flex items-center">
                        @if (!$user->isSchoolLevel() && $SchoolContextHelper->isSchoolContext())
                            <span class="px-1">
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-2 border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 text-gray-800">
                                    <x-icon class="mr-2" icon="arrow-left" />
                                    Back
                                </a>
                            </span>    
                            <x-icon class="px-2" icon="chevron-right fa-xs text-neutral-400" />
                            <button
                                    id="userBtn"
                                    type="button"
                                    data-dropdown-toggle="BreadcrumbSelectSchool"
                                    class="inline-flex items-center px-3 py-2 border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 text-gray-800"
                            >
                                {{ $SchoolContextHelper->getCurrentSchoolContext()->name }}
                                <x-icon  class="px-2" icon="caret-up" hidden />
                                <x-icon  class="px-2" icon="caret-down" />
                            </button>
                            <!-- Dropdown menu -->
                            <div id="BreadcrumbSelectSchool" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                                <div class="p-3">
                                    <label for="input-group-search" class="sr-only">Search</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                            </svg>
                                        </div>
                                        <input type="text" id="input-group-search" class="block w-full p-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search user">
                                    </div>
                                </div>
                                <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButton">
                                    @foreach ($SchoolContextHelper->getSchoolsByFranchise($user->resource->getFranchise()) as $school)
                                        <li>
                                            <div class="flex items-center ps-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <label for="checkbox-item-11" class="w-full py-2 ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">
                                                    {{-- <a href="{{ route('school.view', ['hashedId' => $school->getHashedIdAttribute()]) }}"> code by chromedia --}}
                                                    <a href="{{ route('school.view', ['hashedId' => $school->getCryptedIdAttribute()]) }}"> {{-- code by IT --}}
                                                        {{ $school['name'] }} 
                                                    </a>
                                                </label>
    
                                                @if ($SchoolContextHelper->getCurrentSchoolContext()->id == $school->id)
                                                    <i class="fas fa-check"></i>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
    
                    @impersonating($guard = null)
                        <div class="flex flex-1 items-center justify-center">
                            <div class="flex flex-row bg-[#F5F7FA] gap-4 p-1 border fancy-border rounded border-primary">
                                <div class="flex flex-row items-center gap-2 text-primary text-sm">
                                    <img src="{{ Vite::asset('resources/assets/images/Info.svg') }}" alt="" width="20px" height="20px">
                                    <div>
                                        You're impersonating <span class="font-semibold">{{$user->resource->email}}</span> with <span class="font-semibold">{{ $user->resource->getRole() }}</span> privilege
                                    </div>
                                </div>
                                <div>
                                    <x-button.alert>
                                        <a href="{{ route('impersonate.leave') }}">
                                            Exit
                                            <img class="ml-1" align="right" src="{{ Vite::asset('resources/assets/images/close-round-alert.svg') }}" alt="" width="20px" height="20px">
                                        </a>    
                                    </x-button.alert>
                                </div>
                            </div>
                        </div>
                    @endImpersonating
    
                    <div class="flex flex-row items-center">
                        {{ $user->resource->isSchoolLevel() ? $user->resource?->getSchool()?->name : '' }}
                        <div class="ms-3 relative">
                            <span class="inline-flex rounded-md float-right">
                                <button
                                        id="userBtn"
                                        type="button"
                                        data-dropdown-toggle="userSettingsAction"
                                        class="inline-flex items-center px-3 py-2 border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 text-gray-800"
                                >
                                    <x-avatar-initials id="user-initials" text="{{ $AvatarHelper->getInitials($user->resource) }}" />
                                    <x-icon id="namebarIconUp" class="px-2" icon="caret-up" hidden />
                                    <x-icon id="namebarIconDown" class="px-2" icon="caret-down" />
                                </button>
                                <x-form.dropdownPanel id="userSettingsAction">
                                    <li>
                                        <x-button.dropdownLink onclick="showEditProfile()" as="button" class="hover:bg-primary hover:text-white hover:cursor-pointer">
                                            Edit Profile
                                        </x-button.dropdownLink>
                                    </li>
                                    <li>
                                        <x-button.dropdownLink href="{{ route('logout') }}" method="post" as="button" class="hover:bg-primary hover:text-white">
                                            Log Out
                                        </x-button.dropdownLink>
                                    </li>
                                </x-form.dropdownPanel>
                            </span>
                        </div>
                    </div>
                </header>
                    {{-- <main class="w-full @if(!Session::has('selectedJob') && !Session::has('selectedSeason') && Session::get('openJob') === false) p-4 @endif bg-white h-full overflow-y-scroll rounded-s-lg overflow-hidden pl-4">
                    
                        @if(Session::has('selectedJob') && Session::has('selectedSeason') && Session::get('openJob') === true)
                            <div class="row text-right p-2 mb-3 bg-job-select header-color d-none">
                                <div class="col-12">
                                    <span class="lead m-0 mr-2">
                                        {!! __("You are currently working on <strong>:job</strong> in the <strong>:season</strong> Season", ['job' => $selectedJob['ts_jobname'], 'season' => $selectedSeason['code']]) !!}
                                        <a href="{{route('dashboard.closeJob')}}">[Close Job]</a>                            
                                    </span>
                                </div>
                            </div>
                            @elseif(session()->has('openSeason') && session('openSeason') === true)
                            <div class="row text-right p-2 mb-3 bg-job-select header-color d-none">
                                <div class="col-12">
                                    <span class="lead m-0 mr-2">
                                    {!! __("You are currently working in the <strong>:season</strong> Season", ['season' => $selectedSeasonDashboard['code']]) !!}
                                        <a href="{{route('dashboard.closeSeason')}}">[Close Season]</a>                            
                                    </span>
                                </div>
                            </div>
                            @if(session('openSeason') === true && Route::currentRouteName() === 'dashboard.openSeason')
                                <div class="mt-3"></div>
                                <div class="alert alert-info">Please note that it may take up to 1 minute to Sync down all your Jobs. We will refresh this page automatically.</div>
                            @endif
                        @endif
                        <div class="container3 p-4">
                            <!-- Breadcrumb -->
                            @if(!Session::has('selectedJob') && !Session::has('selectedSeason') && Session::get('openJob') === false)
                                <div class="mt-3"></div>
                            @endif
                            <!-- Breadcrumb -->
                            <div class="container-fluid">
                                <div id="ui-view" style="opacity: 1;">
                                    <div class="animated fadeIn">
                                        @if (isset($slot))
                                            {{ $slot }}
                                        @else
                                            @yield('content')
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </main> --}}

                    <main class="{{ \Illuminate\Support\Arr::toCssClasses([
                        'w-full bg-white h-full overflow-y-scroll rounded-s-lg overflow-hidden pl-4',
                        'p-4' => !session()->has('selectedJob') && !session()->has('selectedSeason') && session('openJob') === false,
                    ]) }}">
                        
                        {{-- Job Open Notification --}}
                        {{-- @if(session()->get('openJob') === true && session()->has('selectedJob') && session()->has('selectedSeason')) --}}
                        @if(session()->get('openJob') === true && !empty($selectedJob['ts_jobname']) && !empty($selectedSeason['code']))
                            <div class="row text-right p-2 mb-3 bg-job-select header-color d-none">
                                <div class="col-12">
                                    <span class="lead m-0 mr-2">
                                        {!! __("You are currently working on <strong>:job</strong> in the <strong>:season</strong> Season", [
                                            'job' => $selectedJob['ts_jobname'],
                                            'season' => $selectedSeason['code']
                                        ]) !!}
                                        <a href="{{ route('dashboard.closeJob') }}">[Close Job]</a>                            
                                    </span>
                                </div>
                            </div>
                    
                        {{-- Season Open Notification --}}
                        @elseif(session()->get('openSeason') === true && !empty($selectedSeasonDashboard['code']))
                            <div class="row text-right p-2 mb-3 bg-job-select header-color d-none">
                                <div class="col-12">
                                    <span class="lead m-0 mr-2">
                                        {!! __("You are currently working in the <strong>:season</strong> Season", [
                                            'season' => $selectedSeasonDashboard['code']
                                        ]) !!}
                                        <a href="{{ route('dashboard.closeSeason') }}">[Close Season]</a>                            
                                    </span>
                                </div>
                            </div>
                    
                            {{-- Info alert only on openSeason route --}}
                            @if(Route::currentRouteName() === 'dashboard.openSeason')
                                <div class="mt-3"></div>
                                <div class="alert alert-info">
                                    Please note that it may take up to 1 minute to Sync down all your Jobs. We will refresh this page automatically.
                                </div>
                            @endif
                        @endif

                        <div id="flash-container">
                            @if ($msg = session()->pull('message'))
                                @include('proofing.franchise.flash-success', ['message' => $msg])
                            @endif
                        
                            @if ($err = session()->pull('error'))
                                @include('proofing.franchise.flash-error', ['message' => $err])
                            @endif
                        </div>
                        
                        <div class="container3 p-4">
                            {{-- Spacer if no job or season --}}
                            @if(!session()->has('selectedJob') && !session()->has('selectedSeason') && session('openJob') === false)
                                <div class="mt-3"></div>
                            @endif
                    
                            <div class="container-fluid">
                                <div id="ui-view" style="opacity: 1;">
                                    <div class="animated fadeIn">
                                        {{-- Slot for components, fallback to content section --}}
                                        @if (isset($slot))
                                            {{ $slot }}
                                        @else
                                            @yield('content')
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </main>
                    
                <x-layout.footer />
            </div>
            <x-modal.base id="editProfileModal" title="Edit Profile" body="components.modal.body" footer="components.modal.footer">
                <x-slot name="body">
                    <x-modal.body>
                        @include('partials.users.forms.edit', ['user' => $user])
                    </x-modal.body>
                </x-slot>
                <x-slot name="footer">
                    <x-modal.footer>
                        <x-button.secondary id="edit-profile-btn-cls" data-modal-hide="editProfileModal">Close</x-button.secondary>
                        <x-button.primary id="edit-profile-btn" type="submit" form="edit-profile-form">Save</x-button.primary>
                    </x-modal.footer>
                </x-slot>
            </x-modal.base>
        </div>
        
        @push('scripts')

        <script type="module">
            import { getCurrentNav, getNavTabId } from "{{ Vite::asset('resources/js/helpers/utils.helper.ts') }}"
            const targetElement = `#${getNavTabId(getCurrentNav())}`;
            if(targetElement === '#tabProofing'){
                $('.header-color').removeClass('d-none');
            }

            // TODO: Implement cloudflare-friendly encryption for session polling
            import { startSessionPolling, createApiToken } from "{{ Vite::asset('resources/js/helpers/session.helper.ts') }}"
            // import { decryptData } from "{{ Vite::asset('resources/js/helpers/encryption.helper.ts') }}"
            const editProfileOptions = {
                onShow: async () => {
                    const form = document.getElementById('edit-profile-form');
                    $("#edit-profile-btn").attr('disabled', 'disabled');
                    $("#edit-profile-btn").text('Loading...');
                    $(`#firstname`).val('');
                    $(`#lastname`).val('');
                    $(`#firstname`).attr('readonly', 'readonly');
                    $(`#lastname`).attr('readonly', 'readonly');
                    if (!localStorage.getItem('api_token')) {
                        await createApiToken();
                    }
                    
                    let response = await fetch('{{ route('api.profile.edit') }}', {
                        method: 'GET',
                        dataType: 'json',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Authorization': `Bearer ${localStorage.getItem('api_token')}`
                        },
                    });
                    
                    if (response.status === 401) {
                        await createApiToken();
                        // Retry the request with the new token
                        response = await fetch('{{ route('api.profile.edit') }}', {
                            method: 'GET',
                            dataType: 'json',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Authorization': `Bearer ${localStorage.getItem('api_token')}`
                            },
                        });
                    }
                    
                    let result = await response.json();
                    $(`#edit-user-nonce`).val(result.nonce);
                    $(`#email`).val(result.user.email || '');
                    $(`#firstname`).val(result.user.firstname || '');
                    $(`#lastname`).val(result.user.lastname || '');
                    $("#edit-profile-btn").removeAttr('disabled');
                    $("#edit-profile-btn").text('Save');
                    $(`#firstname`).removeAttr('readonly');
                    $(`#lastname`).removeAttr('readonly');
                },
            };
            const editProfileModal = new Modal(document.getElementById('editProfileModal'), editProfileOptions);
    
            function showEditProfile() {
                editProfileModal.show();
            }

            $(document).ready(function() {
                setTimeout(function() {
                    $(".alert-dismissible").fadeOut(500, function(){
                        $(this).remove();
                    });
                }, 5000); // Auto-hide after 5 seconds
            });
            
            document.addEventListener('DOMContentLoaded', (event) => {
                window.showEditProfile = showEditProfile;
                const token = localStorage.getItem('api_token') || '';
                // const id = localStorage.getItem('api_token_id') === null ? 0 : decryptData(localStorage.getItem('api_token_id'));
                const id = localStorage.getItem('api_token_id') === null ? 0 : localStorage.getItem('api_token_id');
                if (token === '' || id != {{ $user->id }}) {
                    createApiToken();
                }
                startSessionPolling();
            });
            
            const groupSearchInput = document.getElementById('input-group-search');
            if (groupSearchInput) {
                groupSearchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    const items = document.querySelectorAll('#BreadcrumbSelectSchool ul li');
    
                    items.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        if (text.includes(query)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
    
                let currentIndex = -1;
                groupSearchInput.addEventListener('keydown', function(event) {
                    const items = document.querySelectorAll('#BreadcrumbSelectSchool ul li:not([style*="display: none"])');
    
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        if (currentIndex < items.length - 1) {
                            currentIndex++;
                            items.forEach(item => item.classList.remove('bg-gray-200'));
                            items[currentIndex].classList.add('bg-gray-200');
                            items[currentIndex].scrollIntoView({ block: 'nearest' });
                        }
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        if (currentIndex > 0) {
                            currentIndex--;
                            items.forEach(item => item.classList.remove('bg-gray-200'));
                            items[currentIndex].classList.add('bg-gray-200');
                            items[currentIndex].scrollIntoView({ block: 'nearest' });
                        }
                    } else if (event.key === 'Enter') {
                        event.preventDefault();
                        if (currentIndex >= 0 && currentIndex < items.length) {
                            items[currentIndex].querySelector('a').click();
                        }
                    }
                });
            }

            // Bootstrap 5 jQuery Compatibility Bridge
                if (typeof jQuery !== 'undefined') {
                    const $ = jQuery;
                    const bootstrap = window.bootstrap; // Assuming bootstrap is loaded globally

                    $.fn.modal = function(option) {
                        return this.each(function() {
                            const instance = bootstrap.Modal.getOrCreateInstance(this);
                            if (typeof option === 'string') {
                                instance[option]();
                            }
                        });
                    };
                    
                    // Repeat for tooltips or popovers if you use them
                    $.fn.tooltip = function() {
                        return this.each(function() {
                            new bootstrap.Tooltip(this);
                        });
                    };
                }
        </script>
        
        {{-- Bootstrap and necessary plugins --}}
        <script src="{{ asset('proofing-assets/vendors/js/jquery.min.js') }}"></script>
        <script src="{{ asset('proofing-assets/vendors/js/jquery-ui.min.js') }}"></script>
        {{-- <script src="{{ asset('proofing-assets/vendors/js/popper.min.js') }}"></script> --}}
        <script src="{{ asset('proofing-assets/vendors/js/popper2.11.8.min.js') }}"></script>
        <script src="{{ asset('proofing-assets/vendors/js/bootstrap.bundle.min.js') }}"></script>

        {{-- Pace loader if needed --}}
        <script src="{{ asset('proofing-assets/vendors/js/pace.min.js') }}"></script>

        {{-- App-specific JS (depends on jQuery, Bootstrap) --}}
        <script src="{{ asset('proofing-assets/js/app.js') }}"></script>

        @livewireScripts
    
        {{-- CoreUI main scripts --}}
        @yield('js')
        @stack('scripts')
    </body>

</html>




