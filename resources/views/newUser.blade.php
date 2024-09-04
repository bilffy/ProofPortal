@extends('layouts.authenticated')

@section('content')
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Add New User</h3>
        <div></div>
    </div>
    <div class="relative overflow-x-auto mb-8 gap-4">
        <div class="flex flex-row gap-4 max-w-screen-md">
            <div class="w-full">
                <x-form.input.text
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                    label="Email"
                    placeholder="Email"
                />

                <x-form.input.error class="mt-1 mb-2" />
            </div>
            <div class="w-full"></div>
        </div>
        <div class="flex flex-row gap-4 max-w-screen-md">
            <div class="w-full">
                <x-form.input.text
                    id="fname"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="First Name"
                    label="First Name"
                    placeholder="First Name"
                />
                <x-form.input.error class="mt-1 mb-2" />
            </div>
            <div class="w-full">
                <x-form.input.text
                    id="lname"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="Last Name"
                    label="Last Name"
                    placeholder="Last Name"
                />
                <x-form.input.error class="mt-1 mb-2" />
            </div>
        </div>

        <div class="flex flex-row gap-4 max-w-screen-md">
            <div class="w-full">
                <x-form.select>User Role</x-form.select>
            </div>
            <div class="w-full">
                <x-form.select>Franchise</x-form.select>
            </div>

        </div>

        
    </div>

    
<div class="py-4 max-w-screen-md flex flex-row gap-4 justify-end border-t-[1px] border-t-neutral-400">
    <x-button.secondary onclick="window.history.back()">Cancel</x-button.secondary>
    <x-button.primary>Save</x-button.primary>
</div>
@endsection
