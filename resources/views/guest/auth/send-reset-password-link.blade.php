<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
    <h1 class="text-3xl text-[#02B3DF] mb-4">Forgot Password</h1>

    <div class="mb-4 text-sm text-gray-600">
        A password reset link will be sent to your registered email address.
    </div>
    
    @if (session()->has('status'))
        <div class="mb-4 font-medium text-sm text-green-600 text-success">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="submit">
        @error('email')
            <div class="mb-4 font-medium text-sm text-red-600 text-[#FF0000]">        
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

        <div class="flex w-full items-center justify-between mt-4">
            <x-button.primary type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Send Link</span>
                <span wire:loading><x-spinner.button label="Send Link" /></span>
            </x-button.primary>
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Back to login
            </a>
        </div>
    </form>
</main>