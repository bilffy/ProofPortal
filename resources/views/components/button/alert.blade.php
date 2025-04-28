<x-button.base
{{ $attributes->merge([
    'flavor'=>'alert', 
    'class'=>'px-3 py-2 transition-all hover:transition-all border-none text-white hover:bg-alert-hover',
]) }}
  >
    {{ $slot }}
</x-button.base>
