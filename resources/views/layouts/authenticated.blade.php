@extends('app')

@section('main')
    <div class="flex flex-row">
        <x-layout.navBar />
        <div class="flex flex-col w-full h-screen">
            <header class="w-full flex justify-between pl-4 pr-4 mr-2 py-2 min-h-[68px] ">
                <div class="flex items-center">
                    @php if ($SchoolContextHelper->isSchoolContext()) { @endphp
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
                                                <a href="{{ route('school.view', ['id' => $school['id']]) }}">
                                                    {{ $school['name'] }} 
                                                </a>
                                            </label>

                                            @php if ($SchoolContextHelper->getCurrentSchoolContext()->id == $school['id']) { @endphp
                                                <i class="fas fa-check"></i>
                                            @php } @endphp
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                    @php } @endphp
                    {{--<x-form.dropdownPanel id="BreadcrumbSelectSchool">
                        --}}{{--<li>
                            insert Search field here
                        </li>
                        <li>
                            <x-button.dropdownLink href="" class="hover:bg-primary hover:text-white">
                                School
                            </x-button.dropdownLink>
                        </li>--}}{{--
                        
                        
                        
                    </x-form.dropdownPanel>--}}
                </div>
                {{--<div class="flex flex-1 items-center justify-center">
                    <div class="flex flex-row bg-[#F5F7FA] gap-4 p-1 border fancy-border rounded border-primary">
                        <div class="flex flex-row items-center gap-2 text-primary text-sm">
                            <img src="{{ Vite::asset('resources/assets/images/Info.svg') }}" alt="" width="20px" height="20px">
                            You're impersonating <span class="font-semibold">[User]</span> with <span class="font-semibold">[privilege]</span> privilege
                        </div>
                        <div>
                            <x-button.base class="bg-alert p-1">
                                Exit
                                <img src="{{ Vite::asset('resources/assets/images/close-round-alert.svg') }}" alt="" width="20px" height="20px">
                            </x-button.base>
                        </div>
                    </div>
                </div>--}}
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
                <x-avatar-initials text="{{ $AvatarHelper->getInitials($user->resource) }}" />
                <x-icon id="namebarIconUp" class="px-2" icon="caret-up" hidden />
                <x-icon id="namebarIconDown" class="px-2" icon="caret-down" />
            </button>
            <x-form.dropdownPanel id="userSettingsAction">
                <li>
                    <x-button.dropdownLink href="{{ route('reset.my.password') }}" class="hover:bg-primary hover:text-white">
                        Profile
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
            <main class="w-full p-4 bg-white h-full overflow-y-scroll rounded-s-lg overflow-hidden pl-4">
                @if (isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
            <x-layout.footer />
        </div>
    </div>

    <script>
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

    </script>

    @stack('scripts')
@endsection