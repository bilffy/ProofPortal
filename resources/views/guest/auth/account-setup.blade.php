@php
    $passwordMinLength = config('app.password_min_length');
@endphp

<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
    <h1 class="text-3xl text-[#02B3DF] mb-4"> Account Setup</h1>

    @error('email') <div class="mb-4 font-bold text-3xl text-red-600">{{ $message }}</div> @enderror

    <form wire:submit.prevent="submit"
        x-data="{
            password: @entangle('password'),
            password_confirmation: @entangle('password_confirmation'),
            acceptedTerms: false,
            get isButtonEnabled() { return (this.password.length >= {{ $passwordMinLength }} && /[A-Z]/.test(this.password) && /[a-z]/.test(this.password) && /[0-9]/.test(this.password) && this.password === this.password_confirmation && this.acceptedTerms) },
        }">
        <div class="hidden">
            <input type="email" wire:model.blur="email" required autofocus autocomplete="username" />
            <input type="password" wire:model.blur="token" required autofocus autocomplete="username" />
        </div>

        <div class="flex flex-col mb-4">
            <label for="firstName" class="text-neutral-600">First Name</label>
            <input class="border rounded-md p-2 border-neutral" wire:model.blur="firstName" required placeholder="First Name" autocomplete="firstName" />
            @error('firstName') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="flex flex-col mb-4">
            <label for="lastName" class="text-neutral-600">Last Name</label>
            <input class="border rounded-md p-2 border-neutral" wire:model.blur="lastName" required placeholder="Last Name" autocomplete="lastName" />
            @error('lastName') <span class="error">{{ $message }}</span> @enderror
        </div>
        
        <div class="flex flex-col mb-4">
            <input class="border rounded-md p-2 border-neutral" type="password" wire:model.blur="password" required placeholder="Password" autocomplete="new-password" />
            @error('password') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="flex flex-col mb-4">
            <input class="border rounded-md p-2 border-neutral" type="password" wire:model.blur="password_confirmation" required placeholder="Repeat Password" autocomplete="new-password" />
            @error('password_confirmation') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="ml-4 mb-4">
            <ul class="list-disc">
                <li :class="{ 'text-success font-semibold': password.length >= {{ $passwordMinLength }}, 'text-gray-500': password.length < {{ $passwordMinLength }} }">At least {{ $passwordMinLength }} characters</li>
                <li :class="{ 'text-success font-semibold': /[A-Z]/.test(password), 'text-gray-500': !/[A-Z]/.test(password) }">Include at least 1 uppercase letter</li>
                <li :class="{ 'text-success font-semibold': /[a-z]/.test(password), 'text-gray-500': !/[a-z]/.test(password) }">At least 1 lowercase letter</li>
                <li :class="{ 'text-success font-semibold': /[0-9]/.test(password), 'text-gray-500': !/[0-9]/.test(password) }">At least 1 number</li>
                <li :class="{ 'text-success font-semibold': password === password_confirmation, 'text-gray-500': password !== password_confirmation }">Passwords must match each other</li>
            </ul>
        </div>

        <div class="flex flex-col items-center mb-4">
            <label class="inline-flex">
                <input id="accept-tnc" type="checkbox" x-model="acceptedTerms" wire:model.blur="acceptedTerms" class="form-checkbox mr-2 mt-1 focus:ring-0" />
                <span class="text-xs text-gray-500">I have read the <a href="https://www.msp.com.au/terms-of-use" target="_blank" class="text-blue-600 underline">Terms of Use & Copyright Conditions</a>, and understand it applies to all digital images uploaded by MSP Photography on this Portal site.</a></span>
            </label>
            @error('acceptedTerms')  <span class="error text-alert italic">*{{ $message }}</span> @enderror
        </div>
        <div class="flex w-full items-center justify-between">
            <x-button.primary 
                    class="rounded-md text-sm cursor-pointer bg-none text-[#ffffff] flex flex-row gap-1 px-3 py-2 bg-primary hover:bg-primary-hover transition-all hover:transition-all" 
                    type="submit" 
                    x-bind:disabled="!isButtonEnabled"
            >
                <span wire:loading.remove>Next</span>
                <span wire:loading><x-spinner.button label="Next" /></span>
            </x-button.primary>
        </div>
    </form>
</main>