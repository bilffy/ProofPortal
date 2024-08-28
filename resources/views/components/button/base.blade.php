@props(['type' => 'type', 'textColor' => '#ffffff', 'bg' => 'bg-none', 'flavor'=>'none', 'hollow' => false])

<button
    {{ $attributes->merge([
        'type' => $type,
        'class' => "rounded-md text-sm cursor-pointer text-[{$textColor}]  flex flex-row gap-1 " . ($hollow ? "bg-none border-2 border-solid border-{$flavor} text-{$flavor}":"bg-{$flavor} hover:bg-{$flavor}-hover")
    ]) }}
>
    {{ $slot }}

</button>