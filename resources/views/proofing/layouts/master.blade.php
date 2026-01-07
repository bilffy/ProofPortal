
@php
    $selectedJob = session('selectedJob') ?? '[]';
    $selectedSeason = session('selectedSeason') ?? '[]';
    $selectedSeasonDashboard = session('selectedSeasonDashboard') ?? '[]';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="shortcut icon" href="{{ asset('proofing-assets/img/msp_logo.svg') }}">
        @vite(['resources/css/app.scss', 'resources/js/app.ts'])
        <link href='https://fonts.googleapis.com/css?family=Montserrat' rel='stylesheet'>    
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

        <title>@yield('title')</title>
        
        @yield('css')
        <script>
            var base_url = "{{URL::to('/')}}";
        </script>
    </head>

    <body class="font-sans antialiased">
        @if(session('success'))
            <x-toast-success message="{{  session('success') }}" />
        @endif
        <div class="alert alert-success d-none"></div>
        <div class="flex flex-row">
            <x-layout.navBar />
            <div class="flex flex-col w-full min-h-screen flex flex-col">
                <header class="w-full flex justify-between pl-4 pr-4 mr-2 py-2 min-h-[68px]">
                    <div class="flex items-center">
                        @php
                            // Determine schools safely
                            $schools = collect();
                
                            if ($SchoolContextHelper?->isSchoolContext()) {
                                if ($user->isFranchiseLevel()) {
                                    $schools = $SchoolContextHelper->getSchoolsByFranchise($user->resource?->getFranchise()) ?? collect();
                                } elseif ($user->isSchoolLevel()) {
                                    $schools = $SchoolContextHelper->getSchoolsByUser() ?? collect();
                                }
                            } else {
                                $schools = $user->getAllSchools() ?? collect();
                            }
                
                            $currentSchoolName = $SchoolContextHelper?->getCurrentSchoolContext()->name ?? '';
                        @endphp
                
                        @if ($user->isSuperAdmin() || $user->isAdmin() || $user->isRcUser() || $user->isFranchiseLevel() || $user->isSchoolLevel())
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
                                {{ $currentSchoolName ?: 'Select School' }}
                                <x-icon class="px-2" icon="caret-up" hidden />
                                <x-icon class="px-2" icon="caret-down" />
                            </button>
                
                            <!-- Dropdown menu -->
                            <div id="BreadcrumbSelectSchool" class="z-10 hidden bg-white rounded-lg shadow w-60 dark:bg-gray-700">
                                <div class="p-3">
                                    <label for="input-group-search" class="sr-only">Search</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                            </svg>
                                        </div>
                                        <input type="text" id="input-group-search" class="block w-full p-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search school">
                                    </div>
                                </div>
                                <ul class="h-48 px-3 pb-3 overflow-y-auto text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownSearchButton">
                                    @foreach ($schools as $school)
                                        <li>
                                            <div class="flex items-center ps-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <label class="w-full py-2 ms-2 text-sm font-medium text-gray-900 rounded dark:text-gray-300">
                                                    <a href="{{ route('school.view', ['hashedId' => $school->getCryptedIdAttribute()]) }}">
                                                        {{ $school['name'] }}
                                                    </a>
                                                </label>
                
                                                @if ($SchoolContextHelper?->isSchoolContext() && $SchoolContextHelper?->getCurrentSchoolContext()?->id == $school->id)
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
                                    <img src="{{ Vite::asset('resources/assets/images/Info.svg') }}" alt="" width="20" height="20">
                                    <div>
                                        You're impersonating <span class="font-semibold">{{ $user->resource->email }}</span> with <span class="font-semibold">{{ $user->resource->getRole() }}</span> privilege
                                    </div>
                                </div>
                                <div>
                                    <x-button.base class="bg-alert p-1">
                                        <a href="{{ route('impersonate.leave') }}">
                                            Exit
                                            <img class="ml-1" src="{{ Vite::asset('resources/assets/images/close-round-alert.svg') }}" alt="" width="20" height="20">
                                        </a>
                                    </x-button.base>
                                </div>
                            </div>
                        </div>
                    @endImpersonating
                
                    <div class="flex flex-row items-center">
                        @if (!($user->isSuperAdmin() || $user->isAdmin() || $user->isRcUser() || $user->isFranchiseLevel()))
                            {{ $currentSchoolName }}
                        @endif
                
                        <div class="ms-3 relative">
                            <span class="inline-flex rounded-md float-right">
                                <button
                                    id="userBtn"
                                    type="button"
                                    data-dropdown-toggle="userSettingsAction"
                                    class="inline-flex items-center px-3 py-2 border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 text-gray-800"
                                >
                                    <x-avatar-initials text="{{ $AvatarHelper->getInitials($user->resource) }}" />
                                    <x-icon id="namebarIconUp" class="px-2" icon="caret-up" hidden />
                                    <x-icon id="namebarIconDown" class="px-2" icon="caret-down" />
                                </button>
                                <x-form.dropdownPanel id="userSettingsAction">
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
                
                {{-- Proofing --}} 
                <main class="w-full @if(!Session::has('selectedJob') && !Session::has('selectedSeason') && Session::get('openJob') === false) p-4 @endif bg-white h-full overflow-y-scroll rounded-s-lg overflow-hidden pl-4">
                  
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
                                    @yield('content')
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <x-layout.footer />
            </div>
        </div>


        <script type="module">
            import { getCurrentNav, getNavTabId } from "{{ Vite::asset('resources/js/helpers/utils.helper.ts') }}"
            // TODO: Implement cloudflare-friendly encryption for session polling
            import { startSessionPolling, createApiToken } from "{{ Vite::asset('resources/js/helpers/session.helper.ts') }}"
            // import { decryptData } from "{{ Vite::asset('resources/js/helpers/encryption.helper.ts') }}"
            const targetElement = `#${getNavTabId(getCurrentNav())}`;

            if(targetElement === '#tabProofing'){
                $('.header-color').removeClass('d-none');
            }
            
            document.addEventListener('DOMContentLoaded', (event) => {
                const token = localStorage.getItem('api_token') || '';
                // const id = localStorage.getItem('api_token_id') === null ? 0 : decryptData(localStorage.getItem('api_token_id'), token);
                const id = localStorage.getItem('api_token_id') === null ? 0 : localStorage.getItem('api_token_id');
                if (token === '' || id != {{ $user->id }}) {
                    createApiToken();
                }
                startSessionPolling();
            });
            
            const groupSearchInput = document.getElementById('input-group-search');
            if (groupSearchInput) {
                document.getElementById('input-group-search').addEventListener('input', function() {
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
    
                document.getElementById('input-group-search').addEventListener('keydown', function(event) {
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
    
        </script>
        
        <!-- Bootstrap and necessary plugins -->
        <script src="{{ asset('proofing-assets/vendors/js/jquery.min.js') }}"></script>
        {{-- <script src="{{ asset('proofing-assets/vendors/js/popper.min.js') }}"></script> --}}
        <script src="{{ asset('proofing-assets/vendors/js/popper2.11.8.min.js') }}"></script>

        <script src="{{ asset('proofing-assets/vendors/js/jquery-ui.min.js') }}"></script>
        <script src="{{ asset('proofing-assets/vendors/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('proofing-assets/vendors/js/pace.min.js') }}"></script>
        <script src="{{ asset('proofing-assets/js/app.js') }}"></script>

        <!-- CoreUI main scripts -->
        @yield('js')
        @stack('scripts')
    </body>

</html>




