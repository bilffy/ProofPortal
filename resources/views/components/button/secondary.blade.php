{{--<x-button.base class="px-3 py-2 bg-white text-neutral text-sm hover:bg-neutral hover:text-white transition-all hover:transition-all">
    {{ $slot }}
</x-button.base>--}}
<x-button.base
{{ $attributes->merge([
    'flavor'=>'secondary',
    'class'=>'px-3 py-2 transition-all text:secondary border-secondary hover:transition-all hover:bg-secondary hover:text-white',
    'hollow' => true,
]) }}
  >
    {{ $slot }}
</x-button.base>
