@props(['type' => 'button', 'textColor' => '#ffffff', 'bg' => 'bg-none', 'flavor'=>'none', 'hollow' => false])

<button
    {{ $attributes->merge([
        'type' => $type,
        'class' => "rounded-md text-sm font-semibold h-fit cursor-pointer text-[{$textColor}] flex flex-row gap-1 " . ($hollow ? "bg-none border-2 border-solid border-{$flavor} text-{$flavor} hover:bg-{$flavor}-100":"bg-{$flavor} hover:bg-{$flavor}-hover")
    ]) }}
>
    {{ $slot }}

</button>