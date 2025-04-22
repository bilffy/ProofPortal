<x-button.base
{{ $attributes->merge([
    'flavor'=>'primary',
    'class'=>'px-3 py-2 transition-all hover:transition-all disabled:opacity-50 disabled:cursor-not-allowed hover:bg-primary-hover hover:border-primary-hover text-white',
]) }}
  >
    {{ $slot }}
</x-button.base>
