@props(['type' => 'button', 'textColor' => '#ffffff', 'bg' => 'bg-none', 'flavor'=>'none', 'hollow' => false, 'cursor' => 'cursor-pointer'])

@php
    $colorBorder = "border-$flavor";
    $flavorBg = "bg-$flavor";
    $colorBackground = $hollow ? "bg-none" : $flavorBg;
@endphp

<button
    {{ $attributes->merge([
        'type' => $type,
        'class' => "rounded-md text-sm font-semibold h-fit $cursor flex flex-row gap-1 border-2 border-solid $colorBorder $colorBackground"
    ]) }}
>
    {{ $slot }}

</button>