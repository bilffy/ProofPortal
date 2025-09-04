 <x-button.base
     {{ $attributes->merge([
         'type'=>'button',
         'class'=>'px-2 py-3 bg-none text-neutral-600 hover:bg-[#f0eeec] transition-all hover:transition-all',
     ]) }}
 >
     {{ $slot }}
 </x-button.base> 
 
 