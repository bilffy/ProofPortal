@props(['type' => 'button', 'textColor' => '#ffffff', 'bg' => 'bg-none', 'flavor'=>'none', 'hollow' => false, 'cursor' => 'cursor-pointer'])

<button
    {{ $attributes->merge([
        'type' => $type,
        'class' => "rounded-md text-sm font-semibold h-fit {$cursor} text-[{$textColor}] flex flex-row gap-1 border-2 border-solid border-{$flavor} " . ($hollow ? "bg-none text-{$flavor} hover:bg-{$flavor}-100":"bg-{$flavor} hover:bg-{$flavor}-hover")
    ]) }}
>
    {{ $slot }}

</button>