<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
    <h1 class="text-3xl text-[#02B3DF]">Verification Code</h1>
    <div class="mb-4 text-sm text-gray-600">
        {{ $message }}
    </div>
    <form wire:submit.prevent="submit">
        <div class="hidden">
            <input type="text" wire:model="email" required autocomplete="email" autofocus />
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>
        <div>
            <div class="flex flex-col mb-4">
                <input class="border rounded-md p-2 border-neutral" type="password" wire:model="otp" required autocomplete="otp" autofocus />
            </div>
            @error('otp') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="flex w-full items-center justify-between">
            <x-button.primary type="submit">Verify</x-button.primary>
            <x-button.link wire:click="resendOtp('{{ $email }}')">Resend Verification Code</x-button.link>
        </div>
    </form>
</main>