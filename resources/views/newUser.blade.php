@php
    $franchiseOptions = [];
    $schoolOptions = [];
    foreach ($roles as $role) {
        $roleOptions[$role->id] = $role->name;
    }
    foreach ($franchises as $franchise) {
        $franchiseOptions[$franchise->id] = $franchise->name;
    }
    foreach ($schools as $school) {
        $schoolOptions[$school->id] = $school->name;
    }
    $emailError = !empty($errors->get('email')) ? $errors->get('email')[0] : '';
    $fNameError = !empty($errors->get('firstname')) ? $errors->get('firstname')[0] : '';
    $lNameError = !empty($errors->get('lastname')) ? $errors->get('lastname')[0] : '';
@endphp

@extends('layouts.authenticated')

@section('content')
    <div class="py-4 flex items-center justify-between">
        <h3 class="text-2xl">Add New User</h3>
        <div></div>
    </div>
    <div class="relative overflow-x-auto mb-8 gap-4">
        <form id="add-user-form" method="POST" action="{{ route('user.register') }}">
            @csrf
            <div class="flex flex-row gap-4 max-w-screen-md">
                <div class="w-full">
                    <x-form.input.text
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        type="email"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="username"
                        label="Email"
                        placeholder="Email"
                        labelText="Email"
                        errorMessage="{{ $emailError }}"
                    />
                </div>
                <div class="w-full"></div>
            </div>
            <div class="flex flex-row gap-4 max-w-screen-md">
                <div class="w-full">
                    <x-form.input.text
                        id="fname"
                        name="firstname"
                        value="{{ old('firstname') }}"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="First Name"
                        label="First Name"
                        placeholder="First Name"
                        labelText="First Name"
                        errorMessage="{{ $fNameError }}"
                    />
                </div>
                <div class="w-full">
                    <x-form.input.text
                        id="lname"
                        name="lastname"
                        value="{{ old('lastname') }}"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="Last Name"
                        label="Last Name"
                        placeholder="Last Name"
                        labelText="Last Name"
                        errorMessage="{{ $lNameError }}"
                    />
                </div>
            </div>

            <div class="flex flex-row gap-4 max-w-screen-md">
                <div class="w-full">
                    <x-form.select context="role" :options="$roleOptions" required>User Role</x-form.select>
                </div>
                <div id="level" class="w-full invisible">
                    <x-form.select context="school" :options="$schoolOptions" required>School</x-form.select>
                    <x-form.select context="franchise" :options="$franchiseOptions" required>Franchise</x-form.select>
                </div>
            </div>
            <div class="py-4 max-w-screen-md flex flex-row gap-4 justify-end border-t-[1px] border-t-neutral-400">
                <x-button.secondary onclick="window.history.back()">Cancel</x-button.secondary>
                <x-button.primary type="submit">Save</x-button.primary>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script type="module">
    let user = {{ Js::from($user) }}
    let roles = {{ Js::from($roleOptions) }}

    function hideOrShowSelect(query, event) {
        switch (event) {
            case 'HIDE':
                $(`#select_${query}_label`).removeClass('block');
                $(`#select_${query}_label`).css('display','none');
                $(`#select_${query}`).next().hide();
                break;
            case 'SHOW':
                $(`#select_${query}_label`).addClass('block');
                $(`#select_${query}`).next().show();
                break;
        }
    };

    function updateSelectByRole(role) {
        switch(role) {
            case 'Super Admin':
            case 'Admin':
                $('#level').removeClass('visible');
                $('#level').addClass('invisible');
                hideOrShowSelect('franchise', 'HIDE');
                hideOrShowSelect('school', 'HIDE');
                break;
            case 'Franchise':
                $('#level').removeClass('invisible');
                $('#level').addClass('visible');
                hideOrShowSelect('school', 'HIDE');
                hideOrShowSelect('franchise', 'SHOW');
                break;
            case 'Photo Coordinator':
            case 'School Admin':
            case 'Teacher':
                $('#level').removeClass('invisible');
                $('#level').addClass('visible');
                hideOrShowSelect('school', 'SHOW');
                hideOrShowSelect('franchise', 'HIDE');
                break;
        }
    }
    
    function toggleLevelOptions(event) {
        updateSelectByRole(roles[event.target.value]);
    };

    setTimeout(() => {
        $('#select_role').select2({minimumResultsForSearch: Infinity});
        $('#select_role').change(toggleLevelOptions);
        $('#select_school').select2();
        $('#select_franchise').select2();
        updateSelectByRole(user.role);
    }, 500);
</script>
@endpush