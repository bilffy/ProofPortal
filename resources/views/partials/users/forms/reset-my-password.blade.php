
@php
    $passwordMinLength = config('app.password_min_length');
@endphp

<div class="relative w-1/3 ml-8">
    
        <div class="py-4 flex items-center justify-between mb-3">
            <h3 class="text-2xl">Update Password</h3>
        </div>
        <div class="relative overflow-x-auto mb-2 gap-4">
            <form wire:submit.prevent="submit" x-data="{ password: @entangle('password'), password_confirmation: @entangle('password_confirmation') }">
                
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

                <div class="flex w-full items-center justify-between">
                    <button
                            class="rounded-md text-sm cursor-pointer bg-none text-[#ffffff] flex flex-row gap-1 px-3 py-2 bg-primary text-sm hover:bg-primary-hover transition-all hover:transition-all"
                            type="submit"
                            :disabled="!(password.length >= {{ $passwordMinLength }} && /[A-Z]/.test(password) && /[a-z]/.test(password) && /[0-9]/.test(password) && password === password_confirmation)">
                        <span wire:loading.remove>Update</span>
                        <span wire:loading><x-spinner.button label="Update" /></span>
                    </button>
                </div>
            </form>
        </div>
    
</div>

@push('scripts')
    <script type="module">
    </script>
@endpush