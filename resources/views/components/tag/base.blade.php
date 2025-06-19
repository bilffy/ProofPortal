@props(['hollow' => false, 'flavor' => 'primary'])

@php
    $colorBorder = "border-{$flavor}";
    $flavorBg = "bg-{$flavor}";
    $colorBackground = $hollow ? "bg-none" : $flavorBg;
    $textColor = $hollow ? "text-{$flavor}" : "text-white";
@endphp

<span
    {{ $attributes->merge([
        'class' => "rounded-md text-sm font-semi p-1 h-fit w-fit border-1 border-solid $textColor $colorBorder $colorBackground"
    ]) }}
>
    {{ $slot }}
</span>