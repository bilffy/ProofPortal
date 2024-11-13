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
    <div class="relative mb-8 gap-4">
        <form id="add-user-form" method="POST" action="{{ route('user.register') }}">
            @csrf
            <div class="flex flex-row gap-4 max-w-screen-md">
                <div class="w-full">
                    <div class="mt-1 w-full flex flex-col mb-2">
                        <x-form.input.text
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            type="email"
                            required
                            autofocus
                            label="Email"
                            placeholder="Email"
                        />
                        <x-form.input.error errorMessage="{{$emailError}}" />
                    </div>
                </div>
            </div>
            <div class="flex flex-row gap-4 max-w-screen-md">
                <div class="w-full">
                    <div class="mt-1 w-full flex flex-col mb-2">
                        <x-form.input.text
                            id="fname"
                            name="firstname"
                            value="{{ old('firstname') }}"
                            type="text"
                            required
                            label="First Name"
                            placeholder="First Name"
                        />
                        <x-form.input.error errorMessage="{{$fNameError}}" />
                    </div>
                </div>
                <div class="w-full">
                    <div class="mt-1hz w-full flex flex-col mb-2">
                        <x-form.input.text
                            id="lname"
                            name="lastname"
                            value="{{ old('lastname') }}"
                            type="text"
                            required
                            label="Last Name"
                            placeholder="Last Name"
                        />
                        <x-form.input.error errorMessage="{{$lNameError}}" />
                    </div>
                </div>
            </div>

            <div class="flex flex-row gap-4 max-w-screen-md mb-8">
                <div class="w-full">
                    <x-form.select context="role" :options="$roleOptions" required>User Role</x-form.select>
                </div>
                <div id="level" class="w-full invisible">
                    <x-form.select context="school" :options="$schoolOptions" required>School</x-form.select>
                    <x-form.select context="franchise" :options="$franchiseOptions" required>Franchise</x-form.select>
                </div>
            </div>
            
            @php($previous_url = old('previous_url', strcmp(url()->current(), url()->previous()) ? url()->previous() : route('users')) ?? route('users'))
            <input type="hidden" name="previous_url" value="{{ $previous_url }}">

            <div class="py-4 max-w-screen-md flex flex-row gap-4 justify-end border-t-[1px] border-t-neutral-400">
                <x-button.secondary onclick="window.location='{{ url($previous_url) }}'">Cancel</x-button.secondary>
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

    window.addEventListener('load', () => {
        $('#select_role').select2({minimumResultsForSearch: Infinity});
        $('#select_role').change(toggleLevelOptions);
        $('#select_school').select2();
        $('#select_franchise').select2();
        updateSelectByRole(roles[Object.keys(roles)[0]]);
    });
</script>
@endpush