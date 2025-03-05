<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">    
    <h1 class="text-3xl text-[#02B3DF] mb-4">Login</h1>
    
    @if (session('status'))
        <div class="mb-4 font-bold text-3xl text-green-600 bg-[#eaf3e7]">
            {{ session('status') }}
        </div>
    @endif
    
    <form wire:submit.prevent="submit">
        @csrf
        <div class="flex flex-col mb-4">
            <input
                class="border rounded-md p-2 border-neutral"
                type="email"
                wire:model="email"
                required
                autofocus
                autocomplete="off"
                placeholder="Email"
                class="mt-1 block w-full"
            />
            @error('email') <span class="mt-1 mb-2 text-red-600">{{ $message }}</span> @enderror
        </div>
    
        <div class="flex flex-col mb-4">
            <input
                class="border rounded-md p-2 border-neutral"
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
            <x-button.primary type="submit" class="rounded-md bg-none px-3 py-2 bg-primary text-sm hover:bg-primary-hover transition-all hover:transition-all" wire:loading.attr="disabled">
                <span wire:loading.remove>Login</span>
                <span wire:loading><x-spinner.button label="Login" /></span>
            </x-button.primary>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Forgot your password?
                </a>
            @endif
        </div>
    </form>
</main>

@push('scripts')
<script type="module">
    import { encryptData } from "{{ Vite::asset('resources/js/helpers/encryption.helper.ts') }}"
    
    window.addEventListener('livewire:init', () => {
        Livewire.hook('commit.prepare', ({ component }) => {
            const { ephemeral, reactive } = component;
            const { email, password } = Alpine.raw(reactive);
            
            component.ephemeral = {
                ...component.ephemeral,
                email: encryptData(email),
                password: encryptData(password),
            };
        });
    });
</script>
@endpush
