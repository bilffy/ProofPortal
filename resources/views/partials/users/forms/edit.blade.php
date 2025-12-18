<div>
    <form id="edit-profile-form" method="PATCH" action="{{ route('api.profile.update') }}">
        @csrf
        <input type="hidden" id="edit-user-nonce" name="nonce" value="">
        <div class="flex flex-row gap-4 max-w-screen-md">
            <div class="w-full">
                <div class="mt-1 w-1/2 flex flex-col mb-2">
                    <x-form.input.text
                        id="email"
                        name="email"
                        value="{{ old('email') || !empty($user->resource->email) ? $user->resource->email : '' }}"
                        type="email"
                        readonly
                        label="Email"
                        placeholder="Email"
                    />
                </div>
            </div>
        </div>
        <div class="flex flex-row gap-4 max-w-screen-md">
            <div class="w-full">
                <div class="mt-1 w-full flex flex-col mb-2">
                    <x-form.input.text
                        id="firstname"
                        name="firstname"
                        value="{{ old('firstname') || !empty($user->resource->firstname) ? $user->resource->firstname : '' }}"
                        type="text"
                        autofocus
                        required
                        label="First Name"
                        placeholder="First Name"
                    />
                </div>
            </div>
            <div class="w-full">
                <div class="mt-1 w-full flex flex-col mb-2">
                    <x-form.input.text
                        id="lastname"
                        name="lastname"
                        value="{{ old('lastname') || !empty($user->resource->lastname) ? $user->resource->lastname : '' }}"
                        type="text"
                        required
                        label="Last Name"
                        placeholder="Last Name"
                    />
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script type="module">
    import { decryptData, encryptData } from "{{ Vite::asset('resources/js/helpers/encryption.helper.ts') }}";
    import { createApiToken } from "{{ Vite::asset('resources/js/helpers/session.helper.ts') }}";
    document.addEventListener('DOMContentLoaded', function () {
        $('#firstname, #lastname').on('input', function() {
            const sanitized = sanitizeText(this.value);
            if (this.value !== sanitized) {
                this.value = sanitized;
            }
        });

        $('#edit-profile-form').on('submit', async function(event) {
            event.preventDefault(); // Prevent the default form submission
            $("#edit-profile-btn").attr('disabled', 'disabled');
            $("#edit-profile-btn").text('Saving...');
            let formData = $(this).serializeArray();
            let encryptedData = {};

            formData.forEach(function(item) {
                // encrypt the franchise, school and email before submitting the form
                if (item.name === 'franchise' || item.name === 'school' || item.name === 'email') {
                    // Removed data since it's not needed in Edit Profile
                    // encryptedData[item.name] = encryptData(item.value);
                    return;
                } else if (item.name === 'firstname' || item.name === 'lastname') {
                    encryptedData[item.name] = sanitizeText(item.value).trim();
                    return;
                }
                encryptedData[item.name] = item.value;
            });
            
            // remove any errors from the previous submission
            removeErrorMessages();

            // Check for token
            let token = localStorage.getItem('api_token');
            if (!token) {
                await createApiToken();
                token = localStorage.getItem('api_token');
            }
            $.ajax({
                url: $(this).attr('action'),
                method: $(this).attr('method'),
                data: JSON.stringify(encryptedData),
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                success: function(response) {
                    removeErrorMessages();
                    // refresh authenticated page
                    const fname = encryptedData['firstname'][0];
                    const lname = encryptedData['lastname'][0];
                    $('#user-initials span').text(`${fname}${lname}`.toUpperCase());
                    if (response.hasOwnProperty('nonce')) {
                        $('#edit-user-nonce').val(response.nonce);
                    }
                    showToastMessage();
                },
                error: async function(response) {
                    if (response.status === 401) {
                        // Token might be expired, try to get a new one
                        await createApiToken();
                        // Retry the request with the new token
                        $.ajax({
                            url: $('#edit-profile-form').attr('action'),
                            method: $('#edit-profile-form').attr('method'),
                            data: JSON.stringify(encryptedData),
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${localStorage.getItem('api_token')}`
                            },
                            success: function(response) {
                                removeErrorMessages();
                                const fname = encryptedData['firstname'][0];
                                const lname = encryptedData['lastname'][0];
                                $('#user-initials span').text(`${fname}${lname}`.toUpperCase());
                                showToastMessage();
                            },
                            error: function(retryResponse) {
                                handleErrorResponse(retryResponse);
                            }
                        });
                        return;
                    }
                    handleErrorResponse(response);
                },
                complete: function() {
                    $("#edit-profile-btn").removeAttr('disabled');
                    $("#edit-profile-btn").text('Save');
                }
            });
        });
    });

    function showToastMessage() {
        window.dispatchEvent(new CustomEvent('show-toast-message', { detail: { status: 'success', message: 'Profile successfully updated.' } }));
    }

    function sanitizeText(value) {
        const textWithoutEmojis = removeEmojis(value);
        // Regex for disallowed characters
        const disallowed = /[!@#$%^&*()_+\=\[\]{};:"<>,\.?\/~“”]/g;
        // Replace left and right single quotes with straight quote
        // Replace disallowed characters with ''
        return textWithoutEmojis.replace(/[‘’]/g, "'").replace(disallowed, '');
    }

    function removeEmojis(text) {
        return text.replace(/[^\p{L}\p{N}\p{P}\p{Z}\s]/gu, "");
    }

    function removeErrorMessages() {
        $('#email-error').remove();
        $('#firstname-error').remove();
        $('#lastname-error').remove();
    }

    function handleErrorResponse(response) {
        const jsonResponse = response.responseJSON;
        let errors = jsonResponse.errors;
        if (jsonResponse.hasOwnProperty('nonce')) {
            $('#edit-user-nonce').val(jsonResponse.nonce);
        }
        // iterate the errors object and display the error messages
        for (let key in errors) {
            if (errors.hasOwnProperty(key)) {
                let error = errors[key];
                let errorDiv = document.createElement('div');
                let errorP = document.createElement('p');
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
    }
</script>
@endpush
