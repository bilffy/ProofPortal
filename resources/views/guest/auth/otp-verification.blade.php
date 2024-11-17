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
                <input name="otp" placeholder="Enter OTP" class="border rounded-md p-2 border-neutral" type="password" wire:model="otp" required autocomplete="one-time-code" autofocus />
            </div>
        </div>

        <div class="flex w-full items-center justify-between">
            <x-button.primary type="submit" wire:loading.attr="disabled">
                <span wire:target="submit" wire:loading.remove>Verify</span>
                <span wire:loading wire:target="submit"><x-spinner.button label="Verify" /></span>
            </x-button.primary>
            <x-button.link id="resend-button" disabled wire:click="resendOtp('{{ $email }}')" wire:loading.attr="disabled">
                <span wire:target="resendOtp('{{ $email }}')" wire:loading.remove>Resend Verification Code</span>
                <span wire:loading wire:target="resendOtp('{{ $email }}')">
                    <x-spinner.button label="Resend Verification Code" />
                </span>
            </x-button.link>
        </div>
        <div id="countdown-timer" class="mt-4 text-sm text-gray-600"></div>
    </form>

    <script>
        window.addEventListener("load", function () {
            let countdownTime = {{ $countdownTime }}; // 5 minutes in seconds
            const countdownElement = $('#countdown-timer');
            const resendButton = $('#resend-button');

            function startCountdown() {
                resendButton.prop('disabled', true); // Disable the button initially
                let timerInterval = setInterval(() => {
                    if (countdownTime <= 0) {
                        clearInterval(timerInterval); // Stop the timer when it reaches 0
                        countdownElement.text('');
                        resendButton.prop('disabled', false); // Enable the button
                    } else {
                        let minutes = Math.floor(countdownTime / 60);
                        let seconds = countdownTime % 60;
                        countdownElement.text(
                            `Please wait ${minutes}:${seconds < 10 ? '0' : ''}${seconds} before resending.`
                        );
                        countdownTime--;
                    }
                }, 1000);
            }

            // Start the countdown when the page loads
            startCountdown();

            // Resend OTP logic
            resendButton.click(function () {
                countdownTime = {{ $countdownTime }}; // Reset the timer
                startCountdown();
            });
        });
    </script>
        
</main>