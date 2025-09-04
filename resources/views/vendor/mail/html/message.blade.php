<x-mail::layout>
    {{-- Header --}}
    {{-- REMOVE HEADER
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot:header>
    --}}

        {{-- Body --}}
        {{ $slot }}

        {{-- Subcopy --}}
        @isset($subcopy)
            <x-slot:subcopy>
                <x-mail::subcopy>
                    {{ $subcopy }}
                </x-mail::subcopy>
            </x-slot:subcopy>
        @endisset

        {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            <p style="font-size: 12px; color: #666666; line-height: 1.3;">
                {{$franchise->getBusinessName()}}
                <br/>
                @if (null == $franchise->state)
                    {{$franchise->address}}
                @else
                    {{$franchise->address}}, {{$franchise->state}} {{$franchise->postcode}}
                @endif
                <br/><br/>
                &copy;MSP Photography 2025. All rights reserved.
            </p>
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
