@props(['type' => 'button', 'textColor' => '#ffffff', 'bg' => 'bg-none', 'flavor'=>'none', 'hollow' => false, 'cursor' => 'cursor-pointer'])

@php
    $colorText = "text-[$textColor]";
    $colorBorder = "border-$flavor";
    $flavorBg = "bg-$flavor";
    $colorBackground = $hollow ? "bg-none" : $flavorBg;
    $flavorText = "text-$flavor";

    $hoverBgHollow = "hover:$flavorBg";
    $hoverBgNonHollow = "hover:$flavorBg-hover";
    $hoverBorderHollow = "hover:$colorBorder";
    $hoverBorderNonHollow = "hover:$colorBorder-hover";
    
    $hoverBackground = $hollow ? $hoverBgHollow : $hoverBgNonHollow;
    $hoverBorder = $hollow ? $hoverBorderHollow : $hoverBorderNonHollow;
    $hollowClasses = "$flavorText hover:text-white";
@endphp

<button
    {{ $attributes->merge([
        'type' => $type,
        'class' => "rounded-md text-sm font-semibold h-fit $cursor flex flex-row gap-1 border-2 border-solid $colorBorder $colorBackground $hoverBackground $hoverBorder " . ($hollow ? $hollowClasses : $colorText)
    ]) }}
>
    {{ $slot }}

</button>