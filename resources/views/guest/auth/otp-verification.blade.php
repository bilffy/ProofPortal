<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
    <h1 class="text-3xl text-[#02B3DF]">Verification Code</h1>
    @if ($errors->has('otp'))
        <div class="mb-4 text-sm text-red-600">
    @else
        <div class="mb-4 text-sm text-green-600">
    @endif
        {{ $message }}
    </div>
    
    <form wire:submit.prevent="submit">
        <div class="hidden">
            <input type="text" wire:model="email" required autocomplete="email" autofocus />
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>
        <div>
            <div class="flex flex-col mb-4">
                <input placeholder="Enter OTP" class="border rounded-md p-2 border-neutral" type="password" wire:model="otp" required autocomplete="otp" autofocus />
            </div>
        </div>

        <div class="flex w-full items-center justify-between">
            <x-button.primary type="submit" wire:loading.attr="disabled">
                <span wire:target="submit" wire:loading.remove>Verify</span>
                <span wire:loading wire:target="submit"><x-spinner.button label="Verify" /></span>
            </x-button.primary>
            <x-button.link wire:click="resendOtp('{{ $email }}')" wire:loading.attr="disabled">
                <span wire:target="resendOtp('{{ $email }}')" wire:loading.remove>Resend Verification Code</span>
                <span wire:loading wire:target="resendOtp('{{ $email }}')">
                    <x-spinner.button label="Resend Verification Code" />
                </span>
            </x-button.link>
        </div>
    </form>
</main>