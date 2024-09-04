@extends('app')

<div class="flex flex-row test">
    <x-layout.navBar />
    <div class="flex flex-col w-full h-screen">
        <header class="w-full flex justify-between pl-4 pr-4 mr-2 py-2">
            <div class="flex flex-1 items-center justify-center">
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
            </div>
            <div class="flex flex-row items-center">
                <div class="flex items-center text-sm text-[#586B78] bg-[#D9D9D9] rounded-full px-2 py-0.5">MSP RESOURCE CENTRE</div>
                <div class="ms-3 relative">
                    <span class="inline-flex rounded-md float-right">
                        <button
                            id="userBtn"
                            type="button"
                            onclick="toggleUserOptions()"
                            class="inline-flex items-center px-3 py-2 border-transparent text-sm leading-4 font-medium rounded-md hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 text-gray-800"
                        >
                            {{ $user->name }}
                            <x-icon id="namebarIconUp" class="px-2" icon="caret-up" hidden />
                            <x-icon id="namebarIconDown" class="px-2" icon="caret-down" />
                        </button>
                        <x-form.dropdownPanel>
                            <li>
                                <x-button.dropdownLink href="{{ route('profile.edit') }}" class="hover:bg-primary hover:text-white">
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
        <main class="w-full p-4 bg-white h-full overflow-y-scroll rounded-s-lg overflow-hidden">
            @yield('content')
        </main>
        <x-layout.footer />
    </div>
</div>

<script>
    let showOptions = false;
    function toggleUserOptions() {
        showOptions = !showOptions;
        if (showOptions) {
            $('#dropOptions').slideDown("fast");
            $('#namebarIconUp').show();
            $('#namebarIconDown').hide();
        } else {
            $('#dropOptions').slideUp("fast");
            $('#namebarIconUp').hide();
            $('#namebarIconDown').show();
        }
    }
</script>

@stack('scripts')
