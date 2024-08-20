@props(['type' => 'type', 'textColor' => '#ffffff', 'bg' => 'bg-none'])

<button
    {{ $attributes->merge([
        'type' => $type,
        'class' => "rounded-md text-sm cursor-pointer {$bg} text-[{$textColor}] flex flex-row gap-1"
    ]) }}
>
    {{ $slot }}
</button>
