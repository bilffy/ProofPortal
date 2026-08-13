@php
    $user = auth()->user();
    $contact = "(02) 6933 7722 | helpdesk@msp.com.au";
    $address = 'MSP Photography Resource Centre - 2 Ball Place, Wagga Wagga NSW 2650';
    
    if ($user->isSchoolLevel() || $user->isFranchiseLevel()) {
        $franchise = $user->getFranchise();
        $address = $franchise->name . ' - ' . $franchise->address . ' ' . $franchise->state . ' ' . $franchise->postcode;
        $contact = $franchise->phone . ' | ' . $franchise->email;
    }

    $platform = strtolower((string) config('app.platform', 'production'));
    $footerBg = config("app.platform_footer_colors.{$platform}")
        ?? config('app.platform_footer_colors.production');
@endphp

<footer
    class="p-4 w-full flex justify-evenly text-sm"
    @if ($footerBg)
        style="background-color: {{ $footerBg }}"
    @endif
>
    <!-- <div>Copyright ⓒ 2026 MSP Photography - v{{ config('app.version') }}</div> -->
    <div>Copyright ⓒ 2026 MSP Photography. All rights reserved.</div>
    <!-- <div>You're logged in as <strong>[User]</strong> with <strong>[privilege]</strong> privileges</div> -->
    <div class="flex flex-row gap-4">
        <div class="flex flex-row items-center">
            <img src="{{ Vite::asset('resources/assets/images/location.svg') }}" alt="">
            {{ $address }}
        </div>
        <div class="flex flex-row items-center">
            <img src="{{ Vite::asset('resources/assets/images/nearMe.svg') }}" alt="">
            {{ $contact }}
        </div>
        
    </div>
</footer>
