@props(['hollow' => false])

<span
    {{ $attributes->merge([
        'class' => "bg-white border-1 border-solid rounded-md text-sm font-semi p-1 h-fit w-fit"
    ]) }}
>
    {{ $slot }}
</span>