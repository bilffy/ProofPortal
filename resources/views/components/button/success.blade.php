<x-button.base
{{ $attributes->merge([
    'flavor'=>'success',
    'class'=>'px-3 py-2 transition-all hover:transition-all',
]) }}
  >
    {{ $slot }}
</x-button.base>

