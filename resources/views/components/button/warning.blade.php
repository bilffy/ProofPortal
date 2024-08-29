<x-button.base
{{ $attributes->merge([
    'flavor'=>'warning',
    'class'=>'px-3 py-2 transition-all hover:transition-all',
]) }}
  >
    {{ $slot }}
</x-button.base>

