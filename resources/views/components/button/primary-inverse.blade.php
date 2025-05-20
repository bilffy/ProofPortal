<x-button.base
{{ $attributes->merge([
    'flavor'=>'primary',
    'class'=>'px-3 py-2 transition-all hover:transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:border-solid hover:bg-primary hover:border-primary hover:text-white text-primary',
]) }}
  >
    {{ $slot }}
</x-button.base>
