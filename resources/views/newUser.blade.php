@php
    $franchiseOptions = [];
    $franchiseOptions[''] = null;
    $schoolOptions = [];
    $schoolOptions[''] = null;
    $roleOptions[''] = null;
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
    $roleError = !empty($errors->get('role')) ? $errors->get('role')[0] : '';
    $franchiseError = !empty($errors->get('franchise')) ? $errors->get('franchise')[0] : '';
    $schoolError = !empty($errors->get('school')) ? $errors->get('school')[0] : '';
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
            <input type="hidden" id="nonce" name="nonce" value="{{ $nonce }}">
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
                            id="firstname"
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
                            id="lastname"
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
                    <x-form.select class="w-full" value="{{ old('role') }}" context="role" :options="$roleOptions" required>User Role</x-form.select>
                    <x-form.input.error errorMessage="{{$roleError}}" />
                </div>
                <div id="level" class="w-full invisible">
                    <x-form.select class="w-full"  value="{{ old('school') }}" context="school" :options="$schoolOptions" required>School</x-form.select>
                    <x-form.input.error errorMessage="{{$schoolError}}" />
                    <x-form.select class="w-full" value="{{ old('franchise') }}" context="franchise" :options="$franchiseOptions" required>Franchise</x-form.select>
                    <x-form.input.error errorMessage="{{$franchiseError}}" />
                </div>
            </div>
            
            @php($previous_url = old('previous_url', strcmp(url()->current(), url()->previous()) ? url()->previous() : route('users')) ?? route('users'))
            <input type="hidden" name="previous_url" value="{{ $previous_url }}">

            <div class="py-4 max-w-screen-md flex flex-row gap-4 justify-end border-t-[1px] border-t-neutral-400">
                <x-button.secondary onclick="window.location='{{ url($previous_url) }}'">Cancel</x-button.secondary>
                <x-button.primary id="save-user" type="submit">Save</x-button.primary>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script type="module">
    import JsAesPhp from "{{ Vite::asset('resources/js/helpers/js-aes-php.ts') }}"
    
    let user = {{ Js::from($user) }}
    let roles = {{ Js::from($roleOptions) }}
    var selectedRole = "{{ old('role') }}";
        
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
        $('#select_role').select2({minimumResultsForSearch: Infinity, placeholder: "Select"});
        $('#select_role').change(toggleLevelOptions);
        $('#select_school').select2({placeholder: "Select"});
        $('#select_franchise').select2({placeholder: "Select"});
        
        if (selectedRole) {
            updateSelectByRole(roles[selectedRole]);
        } else {
            updateSelectByRole(roles[Object.keys(roles)[0]]);
        }
    });
    
    function removeErrorMessages() {
        $('#email-error').remove();
        $('#firstname-error').remove();
        $('#lastname-error').remove();
        $('#select_role-error').remove();
        $('#select_franchise-error').remove();
        $('#select_school-error').remove();
    }
    
    document.addEventListener('DOMContentLoaded', async () => {
        $('#add-user-form').on('submit', async function (event) {
            event.preventDefault(); // Prevent the default form submission
            let formData = $(this).serializeArray();
            let data = {};
            let nonce = $('#nonce').val();
            let token = formData.find(item => item.name === '_token').value;
            
            formData.forEach(function (item) {
                if (item.name !== '_token') {
                    data[item.name] = item.value;
                }
            });
            data['exec_datetime'] = new Date();

            // remove any errors from the previous submission
            removeErrorMessages();

            let encryptedData = {};
            // encryptedData['request'] = await JsAesPhp.encrypt(data, nonce);
            encryptedData['request'] = data;
            encryptedData['_token'] = token;
            encryptedData['exec_timestamp'] = Date.now();
            
            $.ajax({
                url: $(this).attr('action'),
                method: $(this).attr('method'),
                data: encryptedData,
                success: function (response) {
                    if (response.redirect_url !== undefined) {
                        window.location.href = response.redirect_url;
                    }
                },
                error: function (response) {
                    let errors = response.responseJSON.errors;
                    // iterate the errors object and display the error messages
                    for (let key in errors) {
                        if (errors.hasOwnProperty(key)) {
                            let error = errors[key];
                            let errorDiv = document.createElement('div');
                            let errorP = document.createElement('p');
                            if (key === 'role') {
                                key = 'select_role';
                            }
                            if (key === 'franchise') {
                                key = 'select_franchise';
                            }
                            if (key === 'school') {
                                key = 'select_school';
                            }
                            errorDiv.classList.add('mt-1');
                            errorDiv.setAttribute('id', `${key}-error`);
                            errorP.classList.add('text-sm', 'text-alert', 'mb-0');
                            errorP.innerText = error;
                            errorDiv.appendChild(errorP);
                            // check if element not exist
                            if ($(`#${key}`).parent().find(`#${key}-error`).length === 0) {
                                $(`#${key}`).parent().append(errorDiv);
                            }
                        }
                    }
                },
                complete: function () {

                }
            });
        });    
    });
</script>
@endpush