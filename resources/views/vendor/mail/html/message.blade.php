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
        <p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif !important; font-size: 14px; color: #666666; line-height: 1.4; text-align: center !important;">
            <br/>
            MSP Photography Pty Ltd
            <br/>
            Copyright &copy; 2026 MSP Photography. All rights reserved.
        </p>
    </x-mail::footer>
</x-slot:footer>
</x-mail::layout>