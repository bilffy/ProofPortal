@php
    $user = auth()->user();
    $contact = "(02) 6933 7722 | helpdesk@msp.com.au";
    $address = 'MSP Photography Resource Centre, 2 Ball Place, Wagga Wagga NSW 2650';
    
    if ($user->isSchoolLevel() || $user->isFranchiseLevel()) {
        $franchise = $user->getFranchise();
        $address = $franchise->address . ' ' . $franchise->state . ' ' . $franchise->postcode;
        $contact = $franchise->name . ' - ' . $franchise->phone . ' | ' . $franchise->email;
    }
@endphp

<footer class="p-4 w-full bg-sky-500 flex justify-evenly text-sm">
    <div>Copyright ⓒ 2024 MSP Photography - Blueprint 1.9.1</div>
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