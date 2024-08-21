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
            <input type="password" wire:model="otp" required autocomplete="otp" autofocus />
            @error('otp') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="flex w-full items-center justify-between">
            <button type="submit">Verify</button>
            <button type="button" wire:click="resendOtp('{{ $email }}')">Resend Verification Code</button>
        </div>
    </form>
</main>