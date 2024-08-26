@props(['href' => '', 'activeNav' => false, 'navIcon' => ''])

<a
    class="flex gap-2 items-center pl-4 pr-4 pt-2 pb-2 border-white {{ $activeNav ? 'bg-primary text-white rounded-e-md' : '' }}"
    href="{{ $href }}">
    {{-- Replace with Fontawesome --}}
    <span class=" w-[20px] h-[20px] flex items-center justify-center">
        {{-- <i class="fa fa-{{ $icon }}"></i> --}}
        <x-icon icon="{{ $navIcon }}"/>
    </span>
    
    {{ $slot }}
    
</a>
