{{--<x-button.base class="px-3 py-2 bg-white text-neutral text-sm hover:bg-neutral hover:text-white transition-all hover:transition-all">
    {{ $slot }}
</x-button.base>--}}
<x-button.base
{{ $attributes->merge([
    'flavor'=>'secondary',
    'class'=>'px-3 py-2 transition-all hover:transition-all',
]) }}
    textColor="neutral"
  >
    {{ $slot }}
</x-button.base>
