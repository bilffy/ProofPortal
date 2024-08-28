<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
    <h1 class="text-3xl text-[#02B3DF] mb-4">Forgot Password</h1>

    <div class="mb-4 text-sm text-gray-600">
        Forgot your password? No problem. Just let us know your email address and we will email you a password reset
        link that will allow you to choose a new one.
    </div>
    
    @if (session()->has('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="submit">
        @error('email')
            <div class="mb-4 font-medium text-sm text-green-600">        
                {{ $message }}
            </div>
        @enderror
        <div>
            <div class="flex flex-col mb-4">
                <input
                        class="border rounded-md p-2 border-neutral"
                        type="email"
                        wire:model="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Email"
                        class="mt-1 block w-full"
                />
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-button.primary type="submit" wire:loading.attr="disabled">Email Password Reset Link</x-button.primary>
        </div>
    </form>
</main>