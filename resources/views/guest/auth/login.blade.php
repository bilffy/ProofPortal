<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">    
    <h1 class="text-3xl text-[#02B3DF] mb-4">Login</h1>
    
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif
    
    <form wire:submit.prevent="submit">
        <div>
            <input
                    type="email"
                    wire:model="email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Email"
                    class="mt-1 block w-full"
            />
            @error('email') <span class="mt-1 mb-2 text-red-600">{{ $message }}</span> @enderror
        </div>
    
        <div class="mt-4">
            <input
                    type="password"
                    wire:model="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                    class="mt-1 block w-full"
            />
            @error('password') <span class="mt-1 mb-2 text-red-600">{{ $message }}</span> @enderror
        </div>
    
        <div class="flex w-full items-center justify-between mt-4">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                Login
            </button>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Forgot your password?
                </a>
            @endif
        </div>
    </form>
</main>