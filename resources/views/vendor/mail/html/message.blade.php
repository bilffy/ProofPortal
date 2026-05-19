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
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="750" style="width: 750px; margin: 0 auto;">
<tr>
<td style="padding: 0; text-align: center;">
<x-mail::footer>
<p style="font-family: 'Montserrat', Helvetica, Arial, sans-serif; font-size: 12px; color: #666666; line-height: 1.3;">
Copyright ⓒ 2026 MSP Photography. All rights reserved.
</p>
</x-mail::footer>
</td>
</tr>
</table>
</x-slot:footer>
</x-mail::layout>
