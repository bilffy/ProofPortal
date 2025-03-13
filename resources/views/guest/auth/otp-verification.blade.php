<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
    <h2 class="text-3xl text-[#02B3DF]">Enter Security Code</h2>
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
                <input name="otp" placeholder="Enter your code" class="border rounded-md p-2 border-neutral" type="password" wire:model="otp" required autocomplete="one-time-code" autofocus />
            </div>
        </div>
        
        <div class="flex w-full items-center justify-between">
            <x-button.primary id="send-button" type="submit" wire:loading.attr="disabled">
                <span wire:target="submit" wire:loading.remove>Continue</span>
                <span wire:loading wire:target="submit"><x-spinner.button label="Verify" /></span>
            </x-button.primary>
            <x-button.link id="resend-button" cursor="" disabled wire:click="resendOtp('{{ $email }}')" wire:loading.attr="disabled" class="opacity-50">
                <span wire:target="resendOtp('{{ $email }}')" wire:loading.remove>Resend Code</span>
                <span wire:loading wire:target="resendOtp('{{ $email }}')">
                    <x-spinner.button label="Resend Code" />
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
            let endTime; // Variable to store the exact end time
            let timeLeft;
            let enabledResendTime = 180; // Enable the resend button when there are 180 seconds left (3 minutes) 
            
            function startCountdown() {
                resendButton.prop('disabled', true); // Disable the button initially
                endTime = Date.now() + countdownTime * 1000; // Calculate the exact end time

                const timerInterval = setInterval(() => {
                    const now = Date.now();
                    timeLeft = Math.round((endTime - now) / 1000); // Calculate remaining seconds

                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    countdownElement.text(
                        `Time remaining ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`
                    );
                    
                    if (timeLeft <= enabledResendTime && timeLeft > 0) {
                        resendButton.prop('disabled', false).removeClass('opacity-50').addClass('cursor-pointer text-orange-500 underline');
                    } else if (timeLeft <= 0) {
                        clearInterval(timerInterval); // Stop the timer when it reaches 0
                        // reload the page
                        location.reload();
                    }
                    else {
                        
                    }
                }, 1000);
            }

            // Start the countdown when the page loads
            startCountdown();

            // Resend OTP
            resendButton.click(function () {
                countdownTime = {{ $countdownTime }}; // Reset the timer
                startCountdown();
            });
        });
    </script>
        
</main>