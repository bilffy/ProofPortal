@php
    $passwordMinLength = config('app.password_min_length');
@endphp

<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
    <h1 class="text-3xl text-[#02B3DF] mb-4">Reset Password</h1>

    @error('email') <span class="error">{{ $message }}</span> @enderror

    <form wire:submit.prevent="submit" x-data="{ password: @entangle('password'), password_confirmation: @entangle('password_confirmation') }">
        <div class="hidden">
            <input type="email" wire:model="email" required autofocus autocomplete="username" />
            <input type="password" wire:model="token" required autofocus autocomplete="username" />
        </div>
        <div class="flex flex-col mb-4">
            <input class="border rounded-md p-2 border-neutral" type="password" wire:model="password" required placeholder="Password" autocomplete="new-password" />
            @error('password') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="flex flex-col mb-4">
            <input class="border rounded-md p-2 border-neutral" type="password" wire:model="password_confirmation" required placeholder="Repeat Password" autocomplete="new-password" />
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

        <div class="flex w-full items-center justify-between">
            <button 
                    class="rounded-md text-sm cursor-pointer bg-none text-[#ffffff] flex flex-row gap-1 px-3 py-2 bg-primary text-sm hover:bg-primary-hover transition-all hover:transition-all" 
                    type="submit" 
                    :disabled="!(password.length >= {{ $passwordMinLength }} && /[A-Z]/.test(password) && /[a-z]/.test(password) && /[0-9]/.test(password) && password === password_confirmation)">
                <span wire:loading.remove>Reset</span>
                <span wire:loading><x-spinner.button label="Reset" /></span>
            </button>
        </div>
    </form>
</main>