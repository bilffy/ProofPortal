@props(['href' => '', 'activeNav' => false, 'imageSrc' => ''])

<a
    class="flex items-center pl-4 pr-4 pt-2 pb-2 border-white {{ $activeNav ? 'bg-primary text-white rounded-e-md' : '' }}"
    href="{{ $href }}">
    {{-- Replace with Fontawesome --}}
    <img 
        width="20px"
        height="20px"
        class="mr-4"
        src={{ $imageSrc }}
        alt="" />
    {{-- Replace with Fontawesome --}}
    {{ $slot }}
    
</a>
