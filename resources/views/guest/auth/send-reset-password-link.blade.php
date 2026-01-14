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
        <input type="hidden" wire:model.blur="nonce">
        @error('email')
            <div class="mb-4 font-medium text-sm text-red-600 text-[#FF0000]">        
                {{ $message }}
            </div>
        @enderror
        <div>
            <div class="flex flex-col mb-4">
                <input
                        class="border rounded-md p-2 border-neutral"
                        id="msp-email"
                        type="email"
                        wire:model.blur="email"
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
        <script type="module">
            // TODO: Implement cloudflare-friendly encryption for forgot password
            // import { encryptObjectValues, encryptData } from "{{ Vite::asset('resources/js/helpers/encryption.helper.ts') }}"
            // import JsAesPhp from "{{ Vite::asset('resources/js/helpers/js-aes-php.ts') }}"
            // document.addEventListener('DOMContentLoaded', async () => {
            //     //$('#msp-email').focus().val('{{ $email }}');
            //     Livewire.hook('commit', ({ commit, component }) => {
            //         if (commit.updates.hasOwnProperty('email')) {
            //             commit.updates = encryptObjectValues(commit.updates);
            //         } else {
            //             var snapshot = JSON.parse(commit.snapshot);
            //             commit.updates = {"email" : encryptData(snapshot.data.email)};
            //         }
            //         return commit;
            //     });
            // });
        </script>
    </form>
</main>